# Local development image: runs the Vite dev server, which also proxies
# /api to the Laravel container. Production would run `npm run build`
# and serve the static files from S3 + CloudFront.
FROM node:24-alpine

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

EXPOSE 5173

CMD ["npx", "vite", "--host", "0.0.0.0", "--port", "5173", "--strictPort"]
