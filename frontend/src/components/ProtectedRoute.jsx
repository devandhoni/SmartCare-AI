import { Navigate } from "react-router-dom";

function getStoredUser() {
    try {
        const rawUser = localStorage.getItem("user");

        if (!rawUser) {
            return null;
        }

        return JSON.parse(rawUser);
    } catch {
        return null;
    }
}

function ProtectedRoute({
    children,
    allowedRoles = [],
}) {
    const token = localStorage.getItem("token");
    const user = getStoredUser();

    if (!token || !user) {
        return <Navigate to="/" replace />;
    }

    if (
        allowedRoles.length > 0 &&
        !allowedRoles.includes(user.role)
    ) {
        const fallback =
            user.role === "Administrator"
                ? "/admin/dashboard"
                : "/today";

        return <Navigate to={fallback} replace />;
    }

    return children;
}

export default ProtectedRoute;