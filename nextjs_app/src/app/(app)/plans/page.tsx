import prisma from "@/lib/prisma";
import Link from "next/link";


const translateStatus = (s: string) => {
  const labels: Record<string, string> = {
    draft: "Brouillon",
    review: "En révision",
    approved: "Approuvé",
    rejected: "Rejeté",
    obsolete: "Obsolète",
  };
  return labels[s] || s;
};

const translatePlanType = (t: string) => {
  const labels: Record<string, string> = {
    architectural: "Architectural",
    structural: "Structurel",
    electrical: "Électrique",
    plumbing: "Plomberie",
    other: "Autre",
  };
  return labels[t] || t;
};

const statusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: "badge-secondary",
    review: "badge-warning",
    approved: "badge-success",
    rejected: "badge-danger",
    obsolete: "badge-dark",
  };
  return classes[status] || "badge-secondary";
};

export default async function PlansPage({
  searchParams,
}: {
  searchParams: { search?: string; status?: string; project?: string; page?: string };
}) {
  const search = searchParams?.search || "";
  const status = searchParams?.status || "";
  const projectIdStr = searchParams?.project || "";
  const page = parseInt(searchParams?.page || "1", 10);
  const limit = 10;
  const offset = (page - 1) * limit;

  // Build where clause
  const where: any = {};
  if (search) {
    where.OR = [
      { title: { contains: search } },
      { planCode: { contains: search } },
    ];
  }
  if (status) {
    where.status = status as string;
  }
  if (projectIdStr) {
    where.projectId = parseInt(projectIdStr, 10);
  }

  // Fetch data
  const total = await prisma.plan.count({ where });
  const plans = await prisma.plan.findMany({
    where,
    orderBy: { createdAt: "desc" },
    skip: offset,
    take: limit,
    include: {
      project: true,
      versions: {
        orderBy: { createdAt: "desc" },
        take: 1
      }
    }
  });

  const allProjects = await prisma.project.findMany({
    orderBy: { name: "asc" }
  });

  const totalPages = Math.ceil(total / limit) || 1;

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">📐 Gestion des Plans</h1>
          <p className="page-subtitle">Plans d'exécution, architecturaux et techniques</p>
        </div>
        <Link href="/plans/create" className="btn btn-primary">
          + Nouveau Plan
        </Link>
      </div>

      <div className="card">
        <form className="filters-bar" method="GET" action="/plans">
          <div className="search-box">
            <input
              type="text"
              name="search"
              className="form-control"
              placeholder="Rechercher (titre, code)..."
              defaultValue={search}
            />
          </div>
          <div className="form-group">
            <select name="project" className="form-control">
              <option value="">Tous les projets</option>
              {allProjects.map(p => (
                <option key={p.id} value={p.id} selected={projectIdStr === String(p.id)}>
                  {p.name}
                </option>
              ))}
            </select>
          </div>
          <div className="form-group">
            <select name="status" className="form-control">
              <option value="">Tous les statuts</option>
              <option value="draft" selected={status === "draft"}>Brouillon</option>
              <option value="review" selected={status === "review"}>En révision</option>
              <option value="approved" selected={status === "approved"}>Approuvé</option>
              <option value="rejected" selected={status === "rejected"}>Rejeté</option>
              <option value="obsolete" selected={status === "obsolete"}>Obsolète</option>
            </select>
          </div>
          <button type="submit" className="btn btn-secondary">
            Filtrer
          </button>
          {(search || status || projectIdStr) && (
            <Link href="/plans" className="btn btn-secondary">
              Réinitialiser
            </Link>
          )}
        </form>

        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Titre</th>
                <th>Projet</th>
                <th>Type</th>
                <th>Version Info</th>
                <th>Statut</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {plans.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucun plan trouvé.
                  </td>
                </tr>
              ) : (
                plans.map((p) => {
                   const latestVersion = p.versions[0];
                   return (
                  <tr key={p.id}>
                    <td><strong>{p.planCode}</strong></td>
                    <td>{p.title}</td>
                    <td>{p.project.name}</td>
                    <td>{translatePlanType(p.planType)}</td>
                    <td>
                      {latestVersion ? (
                        <>
                          <span className="badge badge-info">{latestVersion.versionNumber}</span>
                          <div style={{ fontSize: "0.8rem", color: "var(--text-secondary)", marginTop: "2px" }}>
                            {new Date(latestVersion.createdAt).toLocaleDateString("fr-FR")}
                          </div>
                        </>
                      ) : (
                        <span className="text-muted">Aucune</span>
                      )}
                    </td>
                    <td>
                      <span className={`badge ${statusBadgeClass(p.status)}`}>
                        {translateStatus(p.status)}
                      </span>
                    </td>
                    <td>
                      <div className="table-actions">
                        <Link href={`/plans/view/${p.id}`} className="btn btn-secondary btn-sm" title="Voir">
                          👁️
                        </Link>
                        <Link href={`/plans/edit/${p.id}`} className="btn btn-secondary btn-sm" title="Modifier">
                          ✏️
                        </Link>
                        <Link href={`/plans/delete/${p.id}`} className="btn btn-danger btn-sm" title="Supprimer">
                          🗑️
                        </Link>
                      </div>
                    </td>
                  </tr>
                )})
              )}
            </tbody>
          </table>
        </div>

        {totalPages > 1 && (
          <div className="pagination">
            {page > 1 ? (
              <Link href={`/plans?page=${page - 1}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}>
                &laquo;
              </Link>
            ) : (
              <span className="disabled">&laquo;</span>
            )}

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/plans?page=${p}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}
                className={p === page ? "current" : ""}
              >
                {p}
              </Link>
            ))}

            {page < totalPages ? (
              <Link href={`/plans?page=${page + 1}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}>
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
