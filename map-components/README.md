# Map Components

Drop the files into your React `src/` folder following this structure:

```
src/
  api.js
  components/
    LandingPage.jsx
    loginpage.jsx
    Dashboard.jsx
```

## Install dependencies

```bash
npm install axios react-router-dom @fortawesome/fontawesome-free
```

## Files

| File | Purpose |
|------|---------|
| `api.js` | Axios instance pre-configured for the backend (place in `src/`) |
| `LandingPage.jsx` | Public landing page with hero, stats and how-it-works section |
| `loginpage.jsx` | Login / register page — redirects to `/dashboard` on success |
| `Dashboard.jsx` | Role-based dashboard (client or prestataire) |

## Routes (App.js)

```jsx
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import LandingPage from './components/LandingPage';
import LoginPage   from './components/loginpage';
import Dashboard   from './components/Dashboard';

function PrivateRoute({ children }) {
  return localStorage.getItem('token') ? children : <Navigate to="/login" replace />;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/"          element={<LandingPage />} />
        <Route path="/login"     element={<LoginPage />} />
        <Route path="/dashboard" element={<PrivateRoute><Dashboard /></PrivateRoute>} />
      </Routes>
    </BrowserRouter>
  );
}
```
