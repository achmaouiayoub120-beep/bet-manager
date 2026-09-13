import Link from "next/link";
import { redirect } from "next/navigation";
import prisma from "@/lib/prisma";

export default function LoginPage() {
  async function handleLogin(formData: FormData) {
    "use server";
    
    // Server action for simple login (replace with NextAuth in real app)
    const username = formData.get("username") as string;
    const password = formData.get("password") as string;
    
    if (username && password) {
       // Check against DB
       const user = await prisma.user.findFirst({
         where: {
           OR: [
             { username: username },
             { email: username }
           ]
         }
       });

       if (user && user.isActive) {
          // Fake auth success (No real session here without NextAuth, but we redirect to dashboard for the UI simulation)
          redirect("/dashboard");
       }
    }
    
    // If fail, we redirect back with an error param
    redirect("/auth/login?error=1");
  }

  return (
    <div className="login-page">
      <div className="login-card">
        <div className="login-header">
          <img src="/assets/img/logo.svg" alt="BET Manager" style={{ width: "90px", height: "90px", margin: "0 auto 1rem", display: "block" }} />
          <h1>BET Manager</h1>
          <p>Connectez-vous à votre espace</p>
        </div>

        {/* Note: in a real app, read the 'error' query param. Keeping it static for simulation without client-side hooks if possible. */}
        {/* <div className="alert alert-danger">Identifiants incorrects.</div> */}

        <form action={handleLogin}>
          <div className="form-group">
            <label className="form-label required" htmlFor="username">Nom d'utilisateur ou email</label>
            <input 
              type="text" 
              id="username" 
              name="username" 
              className="form-control" 
              placeholder="admin"
              required 
              autoFocus 
            />
          </div>

          <div className="form-group">
            <label className="form-label required" htmlFor="password">Mot de passe</label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              className="form-control" 
              placeholder="••••••••"
              required 
            />
          </div>

          <button type="submit" className="btn btn-primary btn-block btn-lg">
            Se connecter
          </button>
        </form>

        <p className="text-center text-muted mt-3" style={{ fontSize: "0.8rem" }}>
          © {new Date().getFullYear()} BET Manager - Bureau d'études
        </p>
      </div>
    </div>
  );
}
