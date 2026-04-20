import os
import cv2
import numpy as np
import base64
import requests
import faiss
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List, Optional
import insightface
from insightface.app import FaceAnalysis

app = FastAPI(title="HRM AI Face Service")

# Cấu hình AI Model
# Sử dụng Buffalo_L (tốt nhất) hoặc Buffalo_S (nhanh nhất)
face_app = FaceAnalysis(name='buffalo_l', providers=['CPUExecutionProvider'])
face_app.prepare(ctx_id=0, det_size=(640, 640))

# Quản lý bộ nhớ FAISS
# Dùng IndexFlatIP (Inner Product) cho Cosine Similarity (với vector đã normalize)
# Vector của InsightFace thường là 512 chiều
dimension = 512
index = faiss.IndexFlatIP(dimension)
employee_map = [] # Lưu index tương ứng với employee_code

# URL của Backend PHP để lấy dữ liệu embeddings ban đầu
BE_API_URL = os.getenv("BE_API_URL", "http://hrm-proxy")


@app.get("/health")
async def health_check():
    return {
        "status": "ok",
        "indexed_faces": len(employee_map),
        "be_api_url": BE_API_URL,
    }

class RegisterRequest(BaseModel):
    images: List[str] # Danh sách base64 images

class RecognizeRequest(BaseModel):
    image: str
    threshold: float = 0.6

def decode_base64_image(base64_str: str):
    """
    Decode base64 image string, hỗ trợ:
    - Có hoặc không có data URI prefix (data:image/jpeg;base64,...)
    - Chuỗi thiếu padding '='
    - Whitespace/newline thừa
    """
    if not base64_str:
        return None
    
    # Strip data URI prefix nếu có (data:image/jpeg;base64,...)
    if "base64," in base64_str:
        base64_str = base64_str.split("base64,", 1)[1]
    
    # Strip whitespace và newline có thể có khi truyền qua HTTP
    base64_str = base64_str.strip()
    
    # Thêm padding chuẩn nếu thiếu (độ dài phải chia hết cho 4)
    missing_padding = len(base64_str) % 4
    if missing_padding:
        base64_str += '=' * (4 - missing_padding)
    
    try:
        img_data = base64.b64decode(base64_str)
    except Exception as e:
        print(f"[decode_base64_image] base64 decode failed: {e}")
        return None
    
    nparr = np.frombuffer(img_data, np.uint8)
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    return img

@app.on_event("startup")
async def startup_event():
    """Tải toàn bộ embedding từ DB lên FAISS khi khởi động"""
    print("Loading embeddings from DB...")
    # Lưu ý: Bạn cần có API ở BE trả về toàn bộ vector (internal)
    # Ví dụ mock loading:
    load_embeddings_from_db()

def load_embeddings_from_db():
    global index, employee_map
    # Reset index
    index = faiss.IndexFlatIP(dimension)
    employee_map = []

    try:
        response = requests.get(f"{BE_API_URL}/api/v1/public/face/embeddings", timeout=10)
        response.raise_for_status()
        payload = response.json().get("data", [])
    except Exception as exc:
        print(f"Failed to load embeddings from BE: {exc}")
        return

    vectors = []
    for item in payload:
        vector = item.get("vector")
        employee_code = item.get("employee_code")
        if not vector or not employee_code:
            continue

        vec = np.array(vector, dtype='float32')
        if vec.shape[0] != dimension:
            continue

        norm = np.linalg.norm(vec)
        if norm == 0:
            continue

        vec = vec / norm
        vectors.append(vec)
        employee_map.append(employee_code)

    if vectors:
        index.add(np.stack(vectors))
        print(f"Loaded {len(employee_map)} embeddings into FAISS")
    else:
        print("No embeddings available to load into FAISS")

@app.post("/extract")
async def extract_embeddings(req: RegisterRequest):
    """Trích xuất embeddings từ mảng ảnh ảnh (dành cho đăng ký)"""
    embeddings = []
    for img_b64 in req.images:
        img = decode_base64_image(img_b64)
        if img is None:
            continue
        
        faces = face_app.get(img)
        if not faces:
            continue
        
        # Lấy mặt to nhất
        faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
        emb = faces[0].embedding
        # Normalize để dùng IndexFlatIP tương đương Cosine Similarity
        emb = emb / np.linalg.norm(emb)
        embeddings.append(emb.tolist())
        
    return {"embeddings": embeddings}

@app.post("/search")
async def search_face(req: RecognizeRequest):
    """Tìm kiếm khuôn mặt trong FAISS index (dành cho chấm công)"""
    img = decode_base64_image(req.image)
    if img is None:
        raise HTTPException(
            status_code=422, 
            detail="Không thể decode ảnh: định dạng base64 không hợp lệ hoặc ảnh bị lỗi"
        )
        
    faces = face_app.get(img)
    if not faces:
        return {"match": False, "message": "No face detected"}
    
    # Lấy mặt to nhất
    faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
    query_emb = faces[0].embedding
    query_emb = query_emb / np.linalg.norm(query_emb)
    query_emb = query_emb.reshape(1, -1).astype('float32')

    if index.ntotal == 0:
        return {"match": False, "message": "Database is empty"}

    # Search top-1
    D, I = index.search(query_emb, 1)
    score = float(D[0][0])
    idx = int(I[0][0])

    if idx != -1 and score >= req.threshold:
        return {
            "match": True,
            "employee_code": employee_map[idx],
            "confidence": score
        }
    
    return {"match": False, "confidence": score}

@app.post("/search_v2")
async def search_face_v2(req: RecognizeRequest):
    """Phiên bản tìm kiếm nâng cao (trả về nhiều kết quả hơn nếu cần)"""
    img = decode_base64_image(req.image)
    if img is None:
        raise HTTPException(status_code=422, detail="Invalid image encoding")
        
    faces = face_app.get(img)
    if not faces:
        return {"match": False, "message": "No face detected"}
    
    # Lấy mặt to nhất
    faces = sorted(faces, key=lambda x: (x.bbox[2]-x.bbox[0])*(x.bbox[3]-x.bbox[1]), reverse=True)
    query_emb = faces[0].embedding
    query_emb = query_emb / np.linalg.norm(query_emb)
    query_emb = query_emb.reshape(1, -1).astype('float32')

    if index.ntotal == 0:
        return {"match": False, "message": "Database is empty"}

    # Search top-5
    D, I = index.search(query_emb, 5)
    
    results = []
    for d, i in zip(D[0], I[0]):
        if i != -1 and d >= req.threshold:
            results.append({
                "employee_code": employee_map[int(i)],
                "confidence": float(d)
            })
            
    if results:
        return {
            "match": True,
            "matches": results,
            "best_match": results[0]
        }
    
    return {"match": False, "confidence": float(D[0][0])} 


@app.post("/sync")
async def sync_data(data: List[dict]):
    """
    Đồng bộ dữ liệu vector từ BE sang AI Service
    Input: [{"employee_code": "NV001", "vector": [...]}, ...]
    """
    global index, employee_map
    
    new_index = faiss.IndexFlatIP(dimension)
    new_employee_map = []
    
    vectors = []
    for item in data:
        vec = np.array(item['vector'], dtype='float32')
        # Normalize vector
        vec = vec / np.linalg.norm(vec)
        vectors.append(vec)
        new_employee_map.append(item['employee_code'])
    
    if vectors:
        new_index.add(np.stack(vectors))
        
    index = new_index
    employee_map = new_employee_map
    
    return {"status": "success", "count": len(employee_map)}

if __name__ == "__main__":
    import uvicorn
    # Chạy trên port 6868 như yêu cầu của bạn
    uvicorn.run(app, host="0.0.0.0", port=6868)
