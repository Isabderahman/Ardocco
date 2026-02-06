const http = require('http')

const port = Number.parseInt(process.env.AI_PORT || process.env.PORT || '8001', 10)
const host = '0.0.0.0'

const html = `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ARDOCCO AI</title>
    <style>
      :root { color-scheme: light; }
      body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 40px; background: #0b1220; color: #fff; }
      .card { max-width: 720px; margin: 0 auto; border: 1px solid rgba(255,255,255,.12); border-radius: 18px; padding: 22px; background: rgba(255,255,255,.06); }
      h1 { margin: 0 0 8px; font-size: 20px; letter-spacing: .02em; }
      p { margin: 0; opacity: .85; line-height: 1.5; }
      code { background: rgba(255,255,255,.08); padding: 2px 6px; border-radius: 8px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>ARDOCCO AI</h1>
      <p>This is a placeholder service. Replace <code>ai/server.js</code> with your AI API when ready.</p>
    </div>
  </body>
</html>`

const server = http.createServer((req, res) => {
  if (req.url === '/healthz') {
    res.statusCode = 200
    res.setHeader('content-type', 'application/json; charset=utf-8')
    res.end(JSON.stringify({ ok: true }))
    return
  }

  res.statusCode = 200
  res.setHeader('content-type', 'text/html; charset=utf-8')
  res.end(html)
})

server.listen(port, host, () => {
  // eslint-disable-next-line no-console
  console.log(`AI service listening on http://${host}:${port}`)
})

