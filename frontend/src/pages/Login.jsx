import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { loginUser } from "../api/authApi";

function Login() {
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleLogin = async (e) => {
    e.preventDefault();

    setLoading(true);
    setError("");

    try {
      const result = await loginUser({
        email,
        password,
      });

      const token = result.token;

      const user = result.user;

      if (!token || !user) {
        throw new Error("Invalid login response from server.");
      }

      localStorage.setItem(
        "token", 
        token
    );
      localStorage.setItem(
        "user", 
        JSON.stringify(user)
    );

      const role = user.role;

      if(role === "Administrator")
        {
            navigate("/admin/dashboard");
        }
        else if(role === "Nurse")
        {
            navigate("/nurse/dashboard");
        }
        else
        {
            setError("Unsupported user role.");
        }
    } catch (error) {
      console.error(error);

      const message =
        error?.response?.data?.message ??
        error?.message ??
        "Login failed. Please check your email and password.";

      setError(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100 px-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">

        <div className="mb-8 text-center">
          <h1 className="text-3xl font-bold text-slate-900">
            SmartCare AI
          </h1>

          <p className="mt-2 text-sm text-slate-500">
            Clinical Intelligence & Care Monitoring System
          </p>
        </div>

        {error && (
          <div className="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin}>

          <div className="mb-4">
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Email Address
            </label>

            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              placeholder="Enter your email"
              className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500"
            />
          </div>

          <div className="mb-6">
            <label className="mb-2 block text-sm font-medium text-slate-700">
              Password
            </label>

            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              placeholder="Enter your password"
              className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {loading ? "Signing in..." : "Login"}
          </button>

        </form>

      </div>
    </div>
  );
}

export default Login;