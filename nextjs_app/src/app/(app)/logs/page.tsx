import prisma from "@/lib/prisma";
import Link from "next/link";

export default async function LogsPage({
  searchParams,
}: {
  searchParams: { search?: string; page?: string };
}) {
  const search = searchParams?.search || "";
  const page = parseInt(searchParams?.page || "1", 10);
  const limit = 20;
  const offset = (page - 1) * limit;

  // Build where clause
  const where: any = {};
  if (search) {
    where.description = { contains: search };
  }

  // Fetch data
  const total = await prisma.activityLog.count({ where });
  const logs = await prisma.activityLog.findMany({
    where,
    orderBy: { createdAt: "desc" },
    skip: offset,
    take: limit,
    include: {
      user: true
    }
  });

  const totalPages = Math.ceil(total / limit) || 1;

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">📜 Historique d'activités</h1>
          <p className="page-subtitle">Journal de toutes les actions effectuées dans le système</p>
        </div>
      </div>

      <div className="card">
        <form className="filters-bar" method="GET" action="/logs">
          <div className="search-box">
            <input
              type="text"
              name="search"
              className="form-control"
              placeholder="Rechercher dans l'historique..."
              defaultValue={search}
            />
          </div>
          <button type="submit" className="btn btn-secondary">
            Rechercher
          </button>
          {search && (
            <Link href="/logs" className="btn btn-secondary">
              Réinitialiser
            </Link>
          )}
        </form>

        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Date & Heure</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Type d'entité</th>
                <th>Description</th>
                <th>Adresse IP</th>
              </tr>
            </thead>
            <tbody>
              {logs.length === 0 ? (
                <tr>
                  <td colSpan={6} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucune activité trouvée.
                  </td>
                </tr>
              ) : (
                logs.map((l) => (
                  <tr key={l.id}>
                    <td style={{ whiteSpace: "nowrap" }}>
                      {new Date(l.createdAt).toLocaleString("fr-FR", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </td>
                    <td><strong>{l.user?.fullName || "Système"}</strong></td>
                    <td>
                      <span className="badge badge-secondary">{l.actionType}</span>
                    </td>
                    <td>{l.entityType}</td>
                    <td>{l.description}</td>
                    <td className="text-muted" style={{ fontSize: "0.85rem" }}>{l.ipAddress || "—"}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="pagination">
            {page > 1 ? (
              <Link href={`/logs?page=${page - 1}&search=${encodeURIComponent(search)}`}>
                &laquo;
              </Link>
            ) : (
              <span className="disabled">&laquo;</span>
            )}

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/logs?page=${p}&search=${encodeURIComponent(search)}`}
                className={p === page ? "current" : ""}
              >
                {p}
              </Link>
            ))}

            {page < totalPages ? (
              <Link href={`/logs?page=${page + 1}&search=${encodeURIComponent(search)}`}>
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
