import prisma from "@/lib/prisma";
import Link from "next/link";
import { DocumentType } from "@prisma/client";

const formatFileSize = (bytes: number | null) => {
  if (!bytes) return "—";
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + " Mo";
  if (bytes >= 1024) return (bytes / 1024).toFixed(2) + " Ko";
  return bytes + " octets";
};

const translateDocType = (t: string) => {
  const labels: Record<string, string> = {
    specification: "Spécification",
    contract: "Contrat",
    certificate: "Certificat",
    photo: "Photo",
    other: "Autre",
  };
  return labels[t] || t;
};

export default async function DocumentsPage({
  searchParams,
}: {
  searchParams: { search?: string; type?: string; project?: string; page?: string };
}) {
  const search = searchParams?.search || "";
  const type = searchParams?.type || "";
  const projectIdStr = searchParams?.project || "";
  const page = parseInt(searchParams?.page || "1", 10);
  const limit = 10;
  const offset = (page - 1) * limit;

  // Build where clause
  const where: any = {};
  if (search) {
    where.OR = [
      { title: { contains: search } },
      { fileName: { contains: search } },
    ];
  }
  if (type) {
    where.documentType = type as DocumentType;
  }
  if (projectIdStr) {
    where.projectId = parseInt(projectIdStr, 10);
  }

  // Fetch data
  const total = await prisma.document.count({ where });
  const documents = await prisma.document.findMany({
    where,
    orderBy: { uploadedAt: "desc" },
    skip: offset,
    take: limit,
    include: {
      project: true,
      uploader: true
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
          <h1 className="page-title">📁 Gestion des Documents</h1>
          <p className="page-subtitle">Contrats, spécifications et fichiers joints</p>
        </div>
        <Link href="/documents/upload" className="btn btn-primary">
          + Importer un document
        </Link>
      </div>

      <div className="card">
        <form className="filters-bar" method="GET" action="/documents">
          <div className="search-box">
            <input
              type="text"
              name="search"
              className="form-control"
              placeholder="Rechercher..."
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
            <select name="type" className="form-control">
              <option value="">Tous les types</option>
              <option value="specification" selected={type === "specification"}>Spécification</option>
              <option value="contract" selected={type === "contract"}>Contrat</option>
              <option value="certificate" selected={type === "certificate"}>Certificat</option>
              <option value="photo" selected={type === "photo"}>Photo</option>
              <option value="other" selected={type === "other"}>Autre</option>
            </select>
          </div>
          <button type="submit" className="btn btn-secondary">
            Filtrer
          </button>
          {(search || type || projectIdStr) && (
            <Link href="/documents" className="btn btn-secondary">
              Réinitialiser
            </Link>
          )}
        </form>

        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Fichier</th>
                <th>Titre</th>
                <th>Projet</th>
                <th>Type</th>
                <th>Taille</th>
                <th>Ajouté le</th>
                <th className="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {documents.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aucun document trouvé.
                  </td>
                </tr>
              ) : (
                documents.map((d) => (
                  <tr key={d.id}>
                    <td>
                      <a href={`/documents/download/${d.id}`} target="_blank" rel="noopener noreferrer">
                        {d.fileName}
                      </a>
                    </td>
                    <td><strong>{d.title}</strong></td>
                    <td>{d.project?.name || "—"}</td>
                    <td>{translateDocType(d.documentType)}</td>
                    <td>{formatFileSize(d.fileSize)}</td>
                    <td>{new Date(d.uploadedAt).toLocaleDateString("fr-FR")}</td>
                    <td>
                      <div className="table-actions">
                        <Link href={`/documents/download/${d.id}`} className="btn btn-accent btn-sm" title="Télécharger">
                          ⬇️
                        </Link>
                        <Link href={`/documents/edit/${d.id}`} className="btn btn-secondary btn-sm" title="Modifier">
                          ✏️
                        </Link>
                        <Link href={`/documents/delete/${d.id}`} className="btn btn-danger btn-sm" title="Supprimer">
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
              <Link href={`/documents?page=${page - 1}&search=${encodeURIComponent(search)}&type=${type}&project=${projectIdStr}`}>
                &laquo;
              </Link>
            ) : (
              <span className="disabled">&laquo;</span>
            )}

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={`/documents?page=${p}&search=${encodeURIComponent(search)}&type=${type}&project=${projectIdStr}`}
                className={p === page ? "current" : ""}
              >
                {p}
              </Link>
            ))}

            {page < totalPages ? (
              <Link href={`/documents?page=${page + 1}&search=${encodeURIComponent(search)}&type=${type}&project=${projectIdStr}`}>
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
