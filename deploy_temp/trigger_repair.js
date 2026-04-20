const http = require('http');

const data = JSON.stringify({});

const options = {
  hostname: '127.0.0.1',
  port: 80,
  path: '/api/v1/debug-config/repair-db',
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': data.length
  }
};

const req = http.request(options, (res) => {
  let body = '';
  res.on('data', (chunk) => body += chunk);
  res.on('end', () => {
    console.log('REPAIR STATUS:', res.statusCode);
    console.log('REPAIR RESPONSE:', body);
  });
});

req.on('error', (e) => {
  console.error('REPAIR ERROR:', e.message);
});

req.write(data);
req.end();
