"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import React from "react";

export default function AppLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  // Determine active menu based on pathname
  let activeMenu = "dashboard";
  if (pathname.startsWith("/projects")) activeMenu = "projects";
  if (pathname.startsWith("/plans")) activeMenu = "plans";
  if (pathname.startsWith("/reports")) activeMenu = "reports";
  if (pathname.startsWith("/documents")) activeMenu = "documents";
  if (pathname.startsWith("/users")) activeMenu = "users";
  if (pathname.startsWith("/logs")) activeMenu = "logs";

  // TODO: Replace with real user session data
  const user = {
    name: "Admin",
    role: "admin",
  };

  const translateRole = (role: string) => {
    const labels: Record<string, string> = {
      admin: "Administrateur",
      engineer: "Ingénieur",
      technician: "Technicien",
      viewer: "Consultant",
    };
    return labels[role] || role;
  };

  return (
    <>
      <header className="header">
        <div className="container-fluid header-content">
          <Link href="/dashboard" className="logo">
            <img src="/assets/img/logo-icon.svg" alt="BET Manager" style={{ width: "42px", height: "42px" }} />
            <span>BET Manager</span>
          </Link>
          <div className="header-user">
            <span>{user.name}</span>
            <span className="badge badge-info">{translateRole(user.role)}</span>
            <Link href="/auth/logout" className="btn btn-secondary btn-sm">
              Déconnexion
            </Link>
          </div>
        </div>
      </header>

      <div className="layout">
        <aside className="sidebar">
          <ul className="sidebar-nav">
            <li className="sidebar-section-title">Principal</li>
            <li>
              <Link href="/dashboard" className={activeMenu === "dashboard" ? "active" : ""}>
                📊 Tableau de bord
              </Link>
            </li>
            <li>
              <Link href="/projects" className={activeMenu === "projects" ? "active" : ""}>
                🏗️ Projets
              </Link>
            </li>
            <li>
              <Link href="/plans" className={activeMenu === "plans" ? "active" : ""}>
                📐 Plans
              </Link>
            </li>
            <li>
              <Link href="/reports" className={activeMenu === "reports" ? "active" : ""}>
                📄 Rapports
              </Link>
            </li>
            <li>
              <Link href="/documents" className={activeMenu === "documents" ? "active" : ""}>
                📁 Documents
              </Link>
            </li>

            {user.role === "admin" && (
              <>
                <li className="sidebar-section-title">Administration</li>
                <li>
                  <Link href="/users" className={activeMenu === "users" ? "active" : ""}>
                    👥 Utilisateurs
                  </Link>
                </li>
                <li>
                  <Link href="/logs" className={activeMenu === "logs" ? "active" : ""}>
                    📜 Historique
                  </Link>
                </li>
              </>
            )}
          </ul>
        </aside>

        <main className="main-content">
          {children}
        </main>
      </div>
      
      {/* We can include client side scripts if necessary, but in Next.js it's better to implement them in React. */}
      {/* <script src="/assets/js/script.js"></script> */}
    </>
  );
}
