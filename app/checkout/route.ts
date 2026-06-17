import { readFileSync } from 'fs'
import { join } from 'path'
import { NextResponse } from 'next/server'

export async function GET() {
  const html = readFileSync(join(process.cwd(), 'checkout', 'index.html'), 'utf-8')

  const fixedHtml = html
    .replace(/src="images\//g, 'src="/images/')
    .replace(/url\("images\//g, 'url("/images/')
    .replace(/url\(images\//g, 'url(/images/')

  return new NextResponse(fixedHtml, {
    headers: { 'Content-Type': 'text/html; charset=utf-8' },
  })
}
