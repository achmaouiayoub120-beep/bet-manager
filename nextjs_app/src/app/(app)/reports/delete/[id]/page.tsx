import React from "react";
import Link from "next/link";

export default function Page({ params }: { params: { id: string } }) {
  return (
    <div>
      <div className="page-header">
        <div>
          <h1 className="page-title">Supprimer le/la report {params.id}</h1>
          <p className="page-subtitle">Cette fonctionnalité est en cours de finalisation.</p>
        </div>
        <div className="d-flex gap-1">
          <Link href="/reports" className="btn btn-secondary">Retour à la liste</Link>
        </div>
      </div>
      
      <div className="card">
        <div className="card-header">
          <h3 className="card-title">En construction</h3>
        </div>
        <div className="card-body">
          <p className="text-secondary">
            Cette page a été générée pour éviter l'erreur 404. L'interface complète sera ajoutée prochainement.
          </p>
        </div>
      </div>
    </div>
  );
}
