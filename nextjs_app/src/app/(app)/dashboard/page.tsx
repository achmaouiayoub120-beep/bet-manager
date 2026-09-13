import prisma from "@/lib/prisma";
import DashboardClient from "./DashboardClient";

export default async function DashboardPage() {
  // Fetch stats safely
  const stats = {
    projects: await prisma.project.count(),
    projects_active: await prisma.project.count({ where: { status: "in_progress" } }),
    plans: await prisma.plan.count(),
    reports: await prisma.report.count(),
    documents: await prisma.document.count(),
    users: await prisma.user.count({ where: { isActive: true } }),
  };

  // 1. Projets par statut
  const projectsByStatus = await prisma.project.groupBy({
    by: ["status"],
    _count: { status: true },
  });

  // 2. Plans par type
  const plansByType = await prisma.plan.groupBy({
    by: ["planType"],
    _count: { planType: true },
  });

  // 3. Documents par type
  const docsByType = await prisma.document.groupBy({
    by: ["documentType"],
    _count: { documentType: true },
  });

  // Derniers projets
  const recentProjects = await prisma.project.findMany({
    orderBy: { createdAt: "desc" },
    take: 5,
    select: {
      id: true,
      projectCode: true,
      name: true,
      clientName: true,
      status: true,
      createdAt: true,
    },
  });

  // Activité récente
  const recentActivity = await prisma.activityLog.findMany({
    orderBy: { createdAt: "desc" },
    take: 8,
    include: {
      user: {
        select: {
          fullName: true,
        },
      },
    },
  });

  const userName = "Admin"; // TODO: get from session

  // Formatting data for Client Component
  const formattedProjectsByStatus = projectsByStatus.map(p => ({
    status: p.status,
    count: p._count.status
  }));

  const formattedPlansByType = plansByType.map(p => ({
    planType: p.planType,
    count: p._count.planType
  }));

  const formattedDocsByType = docsByType.map(p => ({
    documentType: p.documentType,
    count: p._count.documentType
  }));

  // Create an array of recent activity matching the PHP structure
  const formattedActivity = recentActivity.map(a => ({
    id: a.id,
    userName: a.user?.fullName || 'Système',
    createdAt: a.createdAt.toISOString(),
    description: a.description || ''
  }));

  const formattedProjects = recentProjects.map(p => ({
    id: p.id,
    projectCode: p.projectCode,
    name: p.name,
    clientName: p.clientName,
    status: p.status,
    createdAt: p.createdAt.toISOString()
  }));

  return (
    <DashboardClient
      userName={userName}
      stats={stats}
      projectsByStatus={formattedProjectsByStatus}
      plansByType={formattedPlansByType}
      docsByType={formattedDocsByType}
      recentProjects={formattedProjects}
      recentActivity={formattedActivity}
    />
  );
}
