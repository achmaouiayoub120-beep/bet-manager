import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "BET Manager",
  description: "Application de gestion pour bureau d'études techniques",
  icons: {
    icon: "/assets/img/favicon.svg",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fr">
      <head>
        <link rel="stylesheet" href="/assets/css/style.css" />
      </head>
      <body>
        {children}
      </body>
    </html>
  );
}
