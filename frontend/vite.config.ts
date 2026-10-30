import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

const backendUrl = process.env.VITE_BACKEND_URL ?? 'http://localhost:8000'

// Proxy the Laravel routes so the SPA and API share an origin and
// Sanctum's session cookies work without any CORS setup.
const proxiedPaths = ['/api', '/login', '/logout', '/register', '/sanctum']

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    // Sanctum only treats localhost:3000 as a stateful SPA origin, so fail
    // loudly instead of silently moving to another port.
    port: 3000,
    strictPort: true,
    proxy: Object.fromEntries(
      proxiedPaths.map((path) => [
        path,
        {
          target: backendUrl,
          // Browser navigations (e.g. GET /login) render the SPA instead.
          bypass: (request) =>
            request.headers.accept?.includes('text/html') ? '/index.html' : undefined,
        },
      ]),
    ),
  },
})
