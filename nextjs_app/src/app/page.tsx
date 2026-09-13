import { redirect } from "next/navigation";

export default function Home() {
  // TODO: Add auth check. If not authenticated, redirect to /auth/login
  redirect("/dashboard");
}
