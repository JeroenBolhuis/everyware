import { put } from '@vercel/blob';

const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

function sanitizeFileName(fileName) {
  return fileName.replace(/[^a-zA-Z0-9._-]/g, '-');
}

export default async function handler(request, response) {
  if (request.method !== 'PUT') {
    response.setHeader('Allow', 'PUT');
    return response.status(405).json({ error: 'Method not allowed.' });
  }

  const fileNameHeader = request.headers['x-file-name'];
  const originalFileName = Array.isArray(fileNameHeader) ? fileNameHeader[0] : fileNameHeader;

  if (!originalFileName) {
    return response.status(422).json({ error: 'Missing file name.' });
  }

  const chunks = [];

  for await (const chunk of request) {
    chunks.push(chunk);
  }

  const body = Buffer.concat(chunks);

  if (body.length === 0) {
    return response.status(422).json({ error: 'Missing file body.' });
  }

  if (body.length > MAX_IMAGE_SIZE) {
    return response.status(413).json({ error: 'File too large.' });
  }

  const contentTypeHeader = request.headers['x-content-type'] || request.headers['content-type'];
  const contentType = Array.isArray(contentTypeHeader) ? contentTypeHeader[0] : contentTypeHeader;
  const safeFileName = sanitizeFileName(originalFileName);
  const pathname = `survey-options/${Date.now()}-${safeFileName}`;

  const blob = await put(pathname, body, {
    access: 'public',
    addRandomSuffix: true,
    contentType: contentType || 'application/octet-stream',
  });

  return response.status(200).json({
    pathname: blob.pathname,
    url: blob.url,
  });
}
