import type { Metadata } from "next";
import { Geist } from "next/font/google";
import "./globals.css";
import ThemeRegistry from '../components/ThemeRegistry';

const geist = Geist({
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "WhatsApp Appointment System",
  description: "Manage appointments and users through WhatsApp",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body className={geist.className}>
        <ThemeRegistry>
          {children}
        </ThemeRegistry>
      </body>
    </html>
  );
}
