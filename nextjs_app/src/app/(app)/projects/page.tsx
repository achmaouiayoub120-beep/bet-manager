import prisma from "@/lib/prisma";
import Link from "next/link";


const translateStatus = (s: string) => {
  const labels: Record<string, string> = {
    planning: "Planification",
    in_progress: "En cours",
    on_hold: "En pause",
    completed: "Terminé",
    cancelled: "Annulé",
  };
  return labels[s] || s;
};

const statusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    planning: "badge-info",
    in_progress: "badge-primary",
    on_hold: "badge-warning",
    completed: "badge-success",
    cancelled: "badge-danger",
  };
  return classes[status] || "badge-secondary";
};

export default async function ProjectsPage({
  searchParams,
}: {
  searchParams: { search?: string; status?: string; page?: string };
}) {
  const search = searchParams?.search || "";
  const status = searchParams?.status || "";
  const page = parseInt(searchParams?.page || "1", 10);
  const limit = 10;
  const offset = (page - 1) * limit;

  // Build where clause
  const where: any = {};
  if (search) {
    where.OR = [
      { name: { contains: search } },
      { projectCode: { contains: search } },
    ];
  }
  if (status) {
    where.status = status as string;
  }

  // Fetch data
  const total = await prisma.project.count({ where });
  const projects = await prisma.project.findMany({
    where,
    orderBy: { createdAt: "desc" },
    skip: offset,
    take: limit,
  });

  const totalPages = Math.ceil(total / limit) || 1;

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">🏗️ Gestion des Projets</h1>
          <p className="page-subtitle">Liste et suivi de tous les projets</p>
        </div>
        <Link href="/projects/create" className="btn btn-primary">
          + Nouveau Projet
        </Link>
      </div>

      <div className="card">
        <form className="filters-bar" method="GET" action="/projects">
          <div className="search-box">
            <input
              type="text"
              name="search"
              className="form-control"
              placeholder="Rechercher (nom, code)..."
              defaultValue={search}
            />
          </div>
          <div className="form-group">
            <select name="status" className="form-control">
              <option value="">Tous les statuts</option>
              <option value="planning" selected={status === "planning"}>Planification</option>
              <option value="in_progress" selected={status === "in_progress"}>En cours</option>
              <option value="on_hold" selected={status === "on_hold"}>En pause</option>
              <option value="completed" selected={status === "completed"}>Terminé</option>
              <option value="cancelled" selected={status === "cancelled"}>Annulé</option>
            </select>
          </div>
          <button type="submit" className="btn btn-secondary">
            Filtrer
          </button>
          {(search || status) && (
            <Link href="/projects" className="btn btn-secondary">
              Réinitialiser
            </Link>
          )}
        </form>

        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom du projet</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Date création</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {projects.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucun projet trouvé.
                  </td>
                </tr>
              ) : (
                projects.map((p) => (
                  <tr key={p.id}>
                    <td><strong>{p.projectCode}</strong></td>
                    <td>{p.name}</td>
                    <td>{p.clientName || "—"}</td>
                    <td>
                      <span className={`badge ${statusBadgeClass(p.status)}`}>
                        {translateStatus(p.status)}
                      </span>
                    </td>
                    <td>{new Date(p.createdAt).toLocaleDateString("fr-FR")}</td>
                    <td>
                      <div className="table-actions">
                        <Link href={`/projects/view/${p.id}`} className="btn btn-secondary btn-sm" title="Voir">
                          👁️
                        </Link>
                        <Link href={`/projects/edit/${p.id}`} className="btn btn-secondary btn-sm" title="Modifier">
                          ✏️
                        </Link>
                        <Link href={`/projects/delete/${p.id}`} className="btn btn-danger btn-sm" title="Supprimer">
                          🗑️
                        </Link>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="pagination">
            {page > 1 ? (
              <Link href={`/projects?page=${page - 1}&search=${encodeURIComponent(search)}&status=${status}`}>
                &laquo;
              </Link>
            ) : (
              <span className="disabled">&laquo;</span>
            )}

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/projects?page=${p}&search=${encodeURIComponent(search)}&status=${status}`}
                className={p === page ? "current" : ""}
              >
                {p}
              </Link>
            ))}

            {page < totalPages ? (
              <Link href={`/projects?page=${page + 1}&search=${encodeURIComponent(search)}&status=${status}`}>
                &raquo;
              </Link>
            ) : (
              <span className="disabled">&raquo;</span>
            )}
          </div>
        )}
      </div>
    </>
  );
}
