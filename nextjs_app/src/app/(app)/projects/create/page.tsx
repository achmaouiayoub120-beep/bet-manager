import Link from "next/link";
import { redirect } from "next/navigation";
import prisma from "@/lib/prisma";


export default function CreateProjectPage() {
  async function createProject(formData: FormData) {
    "use server";
    
    const projectCode = formData.get("project_code") as string;
    const name = formData.get("name") as string;
    const clientName = formData.get("client_name") as string;
    const location = formData.get("location") as string;
    const status = formData.get("status") as string;
    const startDate = formData.get("start_date") as string;
    const endDate = formData.get("end_date") as string;
    const budgetStr = formData.get("budget") as string;
    const description = formData.get("description") as string;

    const budget = budgetStr ? parseFloat(budgetStr) : null;

    try {
      await prisma.project.create({
        data: {
          projectCode,
          name,
          clientName: clientName || null,
          location: location || null,
          status,
          startDate: startDate ? new Date(startDate) : null,
          endDate: endDate ? new Date(endDate) : null,
          budget,
          description: description || null,
          // createdBy: userId (TODO: get from session)
        },
      });
      // TODO: Add activity log
    } catch (e) {
      console.error(e);
      // In a real app we'd return an error state here
    }

    redirect("/projects");
  }

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">Nouveau Projet</h1>
          <p className="page-subtitle">Créer un nouveau projet d'étude</p>
        </div>
        <Link href="/projects" className="btn btn-secondary">
          Retour à la liste
        </Link>
      </div>

      <div className="card" style={{ maxWidth: "800px" }}>
        <div className="card-header">
          <h3 className="card-title">Informations du projet</h3>
        </div>
        
        <form action={createProject}>
          <div className="form-row">
            <div className="form-group">
              <label className="form-label required" htmlFor="project_code">Code Projet</label>
              <input type="text" id="project_code" name="project_code" className="form-control" placeholder="Ex: PRJ-2023-001" required />
            </div>
            <div className="form-group">
              <label className="form-label required" htmlFor="name">Nom du projet</label>
              <input type="text" id="name" name="name" className="form-control" placeholder="Construction immeuble R+5" required />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label className="form-label" htmlFor="client_name">Client / Maître d'ouvrage</label>
              <input type="text" id="client_name" name="client_name" className="form-control" placeholder="Nom du client" />
            </div>
            <div className="form-group">
              <label className="form-label" htmlFor="location">Localisation</label>
              <input type="text" id="location" name="location" className="form-control" placeholder="Ville, Quartier..." />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label className="form-label required" htmlFor="status">Statut</label>
              <select id="status" name="status" className="form-control" required>
                <option value="planning">Planification</option>
                <option value="in_progress">En cours</option>
                <option value="on_hold">En pause</option>
                <option value="completed">Terminé</option>
                <option value="cancelled">Annulé</option>
              </select>
            </div>
            <div className="form-group">
              <label className="form-label" htmlFor="budget">Budget estimé (MAD)</label>
              <input type="number" step="0.01" id="budget" name="budget" className="form-control" placeholder="0.00" />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label className="form-label" htmlFor="start_date">Date de début</label>
              <input type="date" id="start_date" name="start_date" className="form-control" />
            </div>
            <div className="form-group">
              <label className="form-label" htmlFor="end_date">Date de fin prévue</label>
              <input type="date" id="end_date" name="end_date" className="form-control" />
            </div>
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="description">Description et notes</label>
            <textarea id="description" name="description" className="form-control" rows={4} placeholder="Détails du projet..."></textarea>
          </div>

          <div style={{ marginTop: "2rem", borderTop: "1px solid var(--border-light)", paddingTop: "1.5rem", display: "flex", justifyContent: "flex-end", gap: "1rem" }}>
            <Link href="/projects" className="btn btn-secondary">
              Annuler
            </Link>
            <button type="submit" className="btn btn-primary">
              Enregistrer le projet
            </button>
          </div>
        </form>
      </div>
    </>
  );
}
