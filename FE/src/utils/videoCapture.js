const VIDEO_READY_STATE = 2;

const isVideoReady = (video) => (
  Boolean(video)
  && video.readyState >= VIDEO_READY_STATE
  && Number(video.videoWidth) > 0
  && Number(video.videoHeight) > 0
);

const waitForEvent = (target, eventName, resolve) => {
  const handler = () => {
    target.removeEventListener(eventName, handler);
    resolve();
  };
  target.addEventListener(eventName, handler, { once: true });
  return () => target.removeEventListener(eventName, handler);
};

export const waitForVideoReady = async (video, timeoutMs = 4000) => {
  if (!video) {
    throw new Error('camera_not_initialized');
  }

  if (typeof video.play === 'function') {
    try {
      await video.play();
    } catch {
      // Browser autoplay policies can reject play() even when the stream is usable.
    }
  }

  if (isVideoReady(video)) {
    if (typeof video.requestVideoFrameCallback === 'function') {
      await new Promise((resolve) => {
        video.requestVideoFrameCallback(() => resolve());
      });
    }
    return;
  }

  await new Promise((resolve, reject) => {
    let finished = false;
    const cleanupFns = [];

    const cleanup = () => {
      cleanupFns.forEach((fn) => fn());
      cleanupFns.length = 0;
    };

    const complete = (error = null) => {
      if (finished) return;
      finished = true;
      cleanup();
      if (error) {
        reject(error);
        return;
      }
      resolve();
    };

    const poll = window.setInterval(() => {
      if (isVideoReady(video)) {
        complete();
      }
    }, 100);

    const timeout = window.setTimeout(() => {
      complete(new Error('camera_frame_timeout'));
    }, timeoutMs);

    cleanupFns.push(() => window.clearInterval(poll));
    cleanupFns.push(() => window.clearTimeout(timeout));
    cleanupFns.push(waitForEvent(video, 'loadeddata', () => isVideoReady(video) && complete()));
    cleanupFns.push(waitForEvent(video, 'canplay', () => isVideoReady(video) && complete()));
    cleanupFns.push(waitForEvent(video, 'loadedmetadata', () => isVideoReady(video) && complete()));
  });

  if (typeof video.requestVideoFrameCallback === 'function') {
    await new Promise((resolve) => {
      video.requestVideoFrameCallback(() => resolve());
    });
  }
};

const getCoverRect = (sourceWidth, sourceHeight, targetWidth, targetHeight) => {
  const scale = Math.max(targetWidth / sourceWidth, targetHeight / sourceHeight);
  const drawWidth = sourceWidth * scale;
  const drawHeight = sourceHeight * scale;

  return {
    x: (targetWidth - drawWidth) / 2,
    y: (targetHeight - drawHeight) / 2,
    drawWidth,
    drawHeight,
  };
};

const drawMirroredCover = (ctx, drawable, sourceWidth, sourceHeight, targetWidth, targetHeight, mirrored) => {
  const rect = getCoverRect(sourceWidth, sourceHeight, targetWidth, targetHeight);

  ctx.save();
  if (mirrored) {
    ctx.translate(targetWidth, 0);
    ctx.scale(-1, 1);
  }
  ctx.drawImage(drawable, rect.x, rect.y, rect.drawWidth, rect.drawHeight);
  ctx.restore();
};

const createFrameBitmap = async (video) => {
  if (typeof createImageBitmap !== 'function') return null;

  try {
    return await createImageBitmap(video);
  } catch {
    return null;
  }
};

const isBlankFrame = (ctx, width, height) => {
  const pixels = ctx.getImageData(0, 0, width, height).data;
  const strideX = Math.max(1, Math.floor(width / 24));
  const strideY = Math.max(1, Math.floor(height / 24));

  let minLuma = 255;
  let maxLuma = 0;
  let totalLuma = 0;
  let totalSquaredLuma = 0;
  let darkSamples = 0;
  let samples = 0;

  for (let y = 0; y < height; y += strideY) {
    for (let x = 0; x < width; x += strideX) {
      const index = ((y * width) + x) * 4;
      const r = pixels[index];
      const g = pixels[index + 1];
      const b = pixels[index + 2];
      const luma = (0.299 * r) + (0.587 * g) + (0.114 * b);
      minLuma = Math.min(minLuma, luma);
      maxLuma = Math.max(maxLuma, luma);
      totalLuma += luma;
      totalSquaredLuma += luma * luma;
      if (luma < 18) {
        darkSamples += 1;
      }
      samples += 1;
    }
  }

  if (samples === 0) return true;

  const averageLuma = totalLuma / samples;
  const variance = Math.max(0, (totalSquaredLuma / samples) - (averageLuma * averageLuma));
  const stdDev = Math.sqrt(variance);
  const darkRatio = darkSamples / samples;

  if (averageLuma < 6 && stdDev < 6) return true;
  if (averageLuma < 12 && darkRatio > 0.98 && stdDev < 10) return true;
  if ((maxLuma - minLuma) < 8 && darkRatio > 0.99) return true;
  return false;
};

export const captureVideoFrame = async (video, options = {}) => {
  await waitForVideoReady(video, options.timeoutMs);

  const sourceWidth = Number(video.videoWidth);
  const sourceHeight = Number(video.videoHeight);
  if (sourceWidth <= 0 || sourceHeight <= 0) {
    throw new Error('camera_frame_unavailable');
  }

  const targetWidth = Math.max(1, Math.round(Number(options.targetWidth || sourceWidth)));
  const targetHeight = Math.max(1, Math.round(Number(options.targetHeight || sourceHeight)));
  const quality = Number.isFinite(options.quality) ? options.quality : 0.9;
  const mirrored = options.mirrored !== false;

  const canvas = document.createElement('canvas');
  canvas.width = targetWidth;
  canvas.height = targetHeight;

  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  if (!ctx) {
    throw new Error('canvas_unavailable');
  }

  const frameBitmap = await createFrameBitmap(video);
  if (frameBitmap) {
    drawMirroredCover(ctx, frameBitmap, frameBitmap.width, frameBitmap.height, targetWidth, targetHeight, mirrored);
    if (typeof frameBitmap.close === 'function') {
      frameBitmap.close();
    }
  } else {
    drawMirroredCover(ctx, video, sourceWidth, sourceHeight, targetWidth, targetHeight, mirrored);
  }

  if (options.rejectBlankFrame !== false && isBlankFrame(ctx, targetWidth, targetHeight)) {
    throw new Error('blank_frame');
  }

  const dataUrl = canvas.toDataURL(options.type || 'image/jpeg', quality);
  if (typeof dataUrl !== 'string' || !dataUrl.startsWith('data:image/') || dataUrl.length < 128) {
    throw new Error('invalid_capture');
  }

  return {
    dataUrl,
    width: targetWidth,
    height: targetHeight,
  };
};
