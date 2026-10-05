import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Plateforme Nationale de Données Territoriales du Sénégal',
  description: 'Explorez les limites administratives du Sénégal — Régions, Départements, Communes',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="fr">
      <body className="h-screen bg-gray-50">{children}</body>
    </html>
  );
}