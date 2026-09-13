import prisma from "@/lib/prisma";
import Link from "next/link";
import { ReportStatus, ReportType } from "@prisma/client";

const translateStatus = (s: string) => {
  const labels: Record<string, string> = {
    draft: "Brouillon",
    submitted: "Soumis",
    approved: "Approuvé",
    rejected: "Rejeté",
  };
  return labels[s] || s;
};

const translateReportType = (t: string) => {
  const labels: Record<string, string> = {
    technical: "Technique",
    progress: "Avancement",
    inspection: "Inspection",
    calculation: "Calcul",
    other: "Autre",
  };
  return labels[t] || t;
};

const statusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: "badge-secondary",
    submitted: "badge-info",
    approved: "badge-success",
    rejected: "badge-danger",
  };
  return classes[status] || "badge-secondary";
};

export default async function ReportsPage({
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
      { reportCode: { contains: search } },
    ];
  }
  if (status) {
    where.status = status as ReportStatus;
  }
  if (projectIdStr) {
    where.projectId = parseInt(projectIdStr, 10);
  }

  // Fetch data
  const total = await prisma.report.count({ where });
  const reports = await prisma.report.findMany({
    where,
    orderBy: { createdAt: "desc" },
    skip: offset,
    take: limit,
    include: {
      project: true,
      creator: true
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
          <h1 className="page-title">📄 Gestion des Rapports</h1>
          <p className="page-subtitle">Rapports d'inspection, d'avancement et techniques</p>
        </div>
        <Link href="/reports/create" className="btn btn-primary">
          + Nouveau Rapport
        </Link>
      </div>

      <div className="card">
        <form className="filters-bar" method="GET" action="/reports">
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
              <option value="submitted" selected={status === "submitted"}>Soumis</option>
              <option value="approved" selected={status === "approved"}>Approuvé</option>
              <option value="rejected" selected={status === "rejected"}>Rejeté</option>
            </select>
          </div>
          <button type="submit" className="btn btn-secondary">
            Filtrer
          </button>
          {(search || status || projectIdStr) && (
            <Link href="/reports" className="btn btn-secondary">
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
                <th>Auteur</th>
                <th>Statut</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {reports.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucun rapport trouvé.
                  </td>
                </tr>
              ) : (
                reports.map((r) => (
                  <tr key={r.id}>
                    <td><strong>{r.reportCode}</strong></td>
                    <td>{r.title}</td>
                    <td>{r.project?.name || "—"}</td>
                    <td>{translateReportType(r.reportType)}</td>
                    <td>{r.creator?.fullName || "—"}</td>
                    <td>
                      <span className={`badge ${statusBadgeClass(r.status)}`}>
                        {translateStatus(r.status)}
                      </span>
                    </td>
                    <td>
                      <div className="table-actions">
                        <Link href={`/reports/export_pdf/${r.id}`} className="btn btn-accent btn-sm" title="PDF">
                          PDF
                        </Link>
                        <Link href={`/reports/view/${r.id}`} className="btn btn-secondary btn-sm" title="Voir">
                          👁️
                        </Link>
                        <Link href={`/reports/edit/${r.id}`} className="btn btn-secondary btn-sm" title="Modifier">
                          ✏️
                        </Link>
                        <Link href={`/reports/delete/${r.id}`} className="btn btn-danger btn-sm" title="Supprimer">
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
              <Link href={`/reports?page=${page - 1}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}>
                &laquo;
              </Link>
            ) : (
              <span className="disabled">&laquo;</span>
            )}

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/reports?page=${p}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}
                className={p === page ? "current" : ""}
              >
                {p}
              </Link>
            ))}

            {page < totalPages ? (
              <Link href={`/reports?page=${page + 1}&search=${encodeURIComponent(search)}&status=${status}&project=${projectIdStr}`}>
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
