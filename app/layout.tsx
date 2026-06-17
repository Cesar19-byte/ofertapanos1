import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Pano Bom e Barato SP — Kit Pano de Prato',
  description: 'Kit Pano de Prato 100% Algodão com frete grátis para todo o Brasil.',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="pt-BR">
      <body style={{ margin: 0, padding: 0 }}>{children}</body>
    </html>
  )
}
