"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
} from "chart.js";
import { Doughnut, Line, Bar } from "react-chartjs-2";

ChartJS.register(
  ArcElement,
  Tooltip,
  Legend,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement
);

ChartJS.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
ChartJS.defaults.color = "#6c757d";

const colors = {
  navy: "#1a2a3a",
  navyLight: "#2c3e50",
  accent: "#3498db",
  success: "#27ae60",
  warning: "#f39c12",
  danger: "#e74c3c",
  info: "#3498db",
  secondary: "#95a5a6",
  ivory: "#f5f5f0",
  white: "#ffffff",
};

const translateStatusForChart = (s: string) => {
  const labels: Record<string, string> = {
    planning: "Planification",
    in_progress: "En cours",
    on_hold: "En pause",
    completed: "Terminé",
    cancelled: "Annulé",
  };
  return labels[s] || s;
};

const translatePlanTypeForChart = (t: string) => {
  const labels: Record<string, string> = {
    architectural: "Architectural",
    structural: "Structurel",
    electrical: "Électrique",
    plumbing: "Plomberie",
    other: "Autre",
  };
  return labels[t] || t;
};

const translateDocTypeForChart = (t: string) => {
  const labels: Record<string, string> = {
    specification: "Spécification",
    contract: "Contrat",
    certificate: "Certificat",
    photo: "Photo",
    other: "Autre",
  };
  return labels[t] || t;
};

const statusBadgeClass = (status: string) => {
  const classes: Record<string, string> = {
    planning: "badge-info",
    in_progress: "badge-primary",
    on_hold: "badge-warning",
    completed: "badge-success",
    cancelled: "badge-danger",
    draft: "badge-secondary",
    review: "badge-warning",
    approved: "badge-success",
    rejected: "badge-danger",
    obsolete: "badge-dark",
    submitted: "badge-info",
  };
  return classes[status] || "badge-secondary";
};

type Props = {
  userName: string;
  stats: any;
  projectsByStatus: any[];
  plansByType: any[];
  docsByType: any[];
  recentProjects: any[];
  recentActivity: any[];
};

export default function DashboardClient({
  userName,
  stats,
  projectsByStatus,
  plansByType,
  docsByType,
  recentProjects,
  recentActivity,
}: Props) {
  const [mounted, setMounted] = useState(false);
  useEffect(() => setMounted(true), []);

  const projectStatusLabels = projectsByStatus.map((p) => translateStatusForChart(p.status));
  const projectStatusValues = projectsByStatus.map((p) => p.count);
  const statusColorMap: Record<string, string> = {
    planning: "#3498db",
    in_progress: "#1a2a3a",
    on_hold: "#f39c12",
    completed: "#27ae60",
    cancelled: "#e74c3c",
  };
  const projectStatusColors = projectsByStatus.map((p) => statusColorMap[p.status] || "#95a5a6");

  const planTypeLabels = plansByType.map((p) => translatePlanTypeForChart(p.planType));
  const planTypeValues = plansByType.map((p) => p.count);

  const docTypeLabels = docsByType.map((p) => translateDocTypeForChart(p.documentType));
  const docTypeValues = docsByType.map((p) => p.count);
  const docTypeColors = ["#3498db", "#1a2a3a", "#27ae60", "#f39c12", "#95a5a6"];

  // Mock activity for 30 days
  const activityLabels = Array.from({ length: 30 }, (_, i) => {
    const d = new Date();
    d.setDate(d.getDate() - (29 - i));
    return d.toLocaleDateString("fr-FR", { day: "2-digit", month: "2-digit" });
  });
  const activityValues = Array.from({ length: 30 }, () => Math.floor(Math.random() * 5));

  const doughnutOptions: any = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: "bottom",
        labels: {
          padding: 15,
          font: { size: 12 },
          usePointStyle: true,
          pointStyle: "circle",
        },
      },
    },
    cutout: "60%",
  };

  if (!mounted) return null;

  return (
    <>
      <div className="page-header">
        <div>
          <h1 className="page-title">Tableau de bord</h1>
          <p className="page-subtitle">
            Bienvenue, {userName} · {new Date().toLocaleDateString("fr-FR")}
          </p>
        </div>
      </div>

      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-label">Projets totaux</div>
          <div className="stat-value">{stats.projects}</div>
          <div className="stat-description">{stats.projects_active} en cours</div>
        </div>
        <div className="stat-card success">
          <div className="stat-label">Plans</div>
          <div className="stat-value">{stats.plans}</div>
          <div className="stat-description">Tous projets confondus</div>
        </div>
        <div className="stat-card warning">
          <div className="stat-label">Rapports</div>
          <div className="stat-value">{stats.reports}</div>
          <div className="stat-description">Tous types</div>
        </div>
        <div className="stat-card">
          <div className="stat-label">Documents</div>
          <div className="stat-value">{stats.documents}</div>
          <div className="stat-description">Fichiers importés</div>
        </div>
      </div>

      <div className="charts-grid">
        <div className="chart-card">
          <div className="chart-card-header">
            <h3 className="chart-card-title">🏗️ Répartition des projets par statut</h3>
          </div>
          <div className="chart-container-doughnut">
            {projectStatusValues.length === 0 ? (
              <div className="empty-state" style={{ padding: "2rem 0" }}>
                <p className="text-muted">Aucun projet</p>
              </div>
            ) : (
              <Doughnut
                data={{
                  labels: projectStatusLabels,
                  datasets: [
                    {
                      data: projectStatusValues,
                      backgroundColor: projectStatusColors,
                      borderWidth: 2,
                      borderColor: colors.white,
                    },
                  ],
                }}
                options={doughnutOptions}
              />
            )}
          </div>
        </div>

        <div className="chart-card">
          <div className="chart-card-header">
            <h3 className="chart-card-title">📁 Répartition des documents</h3>
          </div>
          <div className="chart-container-doughnut">
            {docTypeValues.length === 0 ? (
              <div className="empty-state" style={{ padding: "2rem 0" }}>
                <p className="text-muted">Aucun document</p>
              </div>
            ) : (
              <Doughnut
                data={{
                  labels: docTypeLabels,
                  datasets: [
                    {
                      data: docTypeValues,
                      backgroundColor: docTypeColors.slice(0, docTypeValues.length),
                      borderWidth: 2,
                      borderColor: colors.white,
                    },
                  ],
                }}
                options={doughnutOptions}
              />
            )}
          </div>
        </div>
      </div>

      <div className="chart-card" style={{ marginBottom: "1.5rem" }}>
        <div className="chart-card-header">
          <h3 className="chart-card-title">📈 Activité des 30 derniers jours</h3>
        </div>
        <div className="chart-container">
          <Line
            data={{
              labels: activityLabels,
              datasets: [
                {
                  label: "Actions",
                  data: activityValues,
                  borderColor: colors.navy,
                  backgroundColor: "rgba(26, 42, 58, 0.08)",
                  borderWidth: 2,
                  fill: true,
                  tension: 0.35,
                  pointRadius: 3,
                  pointHoverRadius: 6,
                  pointBackgroundColor: colors.navy,
                  pointBorderColor: colors.white,
                  pointBorderWidth: 2,
                },
              ],
            }}
            options={{
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: colors.navy,
                  titleColor: colors.white,
                  bodyColor: colors.white,
                  padding: 10,
                  displayColors: false,
                  callbacks: {
                    label: (context) => {
                      const y = context.parsed.y || 0;
                      return y + " action" + (y > 1 ? "s" : "");
                    },
                  },
                },
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: { stepSize: 1, precision: 0 },
                  grid: { color: "rgba(224, 224, 224, 0.5)" },
                },
                x: {
                  grid: { display: false },
                  ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
                },
              },
            }}
          />
        </div>
      </div>

      <div className="chart-card" style={{ marginBottom: "1.5rem" }}>
        <div className="chart-card-header">
          <h3 className="chart-card-title">📐 Plans par type</h3>
        </div>
        <div className="chart-container">
          {planTypeValues.length === 0 ? (
            <div className="empty-state" style={{ padding: "2rem 0" }}>
              <p className="text-muted">Aucun plan</p>
            </div>
          ) : (
            <Bar
              data={{
                labels: planTypeLabels,
                datasets: [
                  {
                    label: "Nombre de plans",
                    data: planTypeValues,
                    backgroundColor: colors.accent,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 60,
                  },
                ],
              }}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: { display: false },
                  tooltip: {
                    backgroundColor: colors.navy,
                    titleColor: colors.white,
                    bodyColor: colors.white,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                      label: (context) => {
                        const y = context.parsed.y || 0;
                        return y + " plan" + (y > 1 ? "s" : "");
                      },
                    },
                  },
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 },
                    grid: { color: "rgba(224, 224, 224, 0.5)" },
                  },
                  x: { grid: { display: false } },
                },
              }}
            />
          )}
        </div>
      </div>

      <div className="charts-grid">
        <div className="card">
          <div className="card-header">
            <h3 className="card-title">Derniers projets</h3>
            <Link href="/projects" className="btn btn-secondary btn-sm">
              Voir tout
            </Link>
          </div>
          {recentProjects.length === 0 ? (
            <div className="empty-state" style={{ padding: "2rem 1rem" }}>
              <p className="text-muted">Aucun projet.</p>
            </div>
          ) : (
            <ul style={{ listStyle: "none", padding: 0 }}>
              {recentProjects.map((p) => (
                <li key={p.id} style={{ padding: "0.75rem 0", borderBottom: "1px solid var(--border-light)" }}>
                  <Link href={`/projects/view/${p.id}`} style={{ fontWeight: 600 }}>
                    {p.name}
                  </Link>
                  <div className="text-muted" style={{ fontSize: "0.85rem", marginTop: "0.25rem" }}>
                    {p.projectCode}
                    {p.clientName ? ` · ${p.clientName}` : ""}
                    {" · "}
                    <span className={`badge ${statusBadgeClass(p.status)}`}>
                      {translateStatusForChart(p.status)}
                    </span>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="card">
          <div className="card-header">
            <h3 className="card-title">Activité récente</h3>
            <Link href="/logs" className="btn btn-secondary btn-sm">
              Voir tout
            </Link>
          </div>
          {recentActivity.length === 0 ? (
            <div className="empty-state" style={{ padding: "2rem 1rem" }}>
              <p className="text-muted">Aucune activité.</p>
            </div>
          ) : (
            <ul style={{ listStyle: "none", padding: 0 }}>
              {recentActivity.map((log) => (
                <li key={log.id} style={{ padding: "0.6rem 0", borderBottom: "1px solid var(--border-light)" }}>
                  <div style={{ fontSize: "0.9rem" }}>
                    <strong>{log.userName}</strong>
                    <span className="text-muted">
                      {" · "}
                      {new Date(log.createdAt).toLocaleString("fr-FR", {
                        day: "2-digit",
                        month: "2-digit",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </span>
                  </div>
                  <div className="text-muted" style={{ fontSize: "0.85rem", marginTop: "0.2rem" }}>
                    {log.description}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </>
  );
}
