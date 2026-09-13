import prisma from "@/lib/prisma";
import Link from "next/link";
import { Role } from "@prisma/client";

const translateRole = (role: string) => {
  const labels: Record<string, string> = {
    admin: "Administrateur",
    engineer: "Ingénieur",
    technician: "Technicien",
    viewer: "Consultant",
  };
  return labels[role] || role;
};

export default async function UsersPage() {
  const users = await prisma.user.findMany({
    orderBy: { fullName: "asc" }
  });

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">👥 Gestion des Utilisateurs</h1>
          <p className="page-subtitle">Administration des accès au système</p>
        </div>
        <Link href="/users/create" className="btn btn-primary">
          + Nouvel Utilisateur
        </Link>
      </div>

      <div className="card">
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Nom complet</th>
                <th>Nom d'utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucun utilisateur trouvé.
                  </td>
                </tr>
              ) : (
                users.map((u) => (
                  <tr key={u.id}>
                    <td><strong>{u.fullName}</strong></td>
                    <td>{u.username}</td>
                    <td>{u.email}</td>
                    <td><span className="badge badge-info">{translateRole(u.role)}</span></td>
                    <td>
                      {u.isActive ? (
                        <span className="badge badge-success">Actif</span>
                      ) : (
                        <span className="badge badge-danger">Inactif</span>
                      )}
                    </td>
                    <td>
                      <div className="table-actions">
                        <Link href={`/users/edit/${u.id}`} className="btn btn-secondary btn-sm" title="Modifier">
                          ✏️
                        </Link>
                        {u.username !== "admin" && (
                          <Link href={`/users/delete/${u.id}`} className="btn btn-danger btn-sm" title="Supprimer">
                            🗑️
                          </Link>
                        )}
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
