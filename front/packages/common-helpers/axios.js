import axios from 'axios';

const baseURL = import.meta.env.VITE_BASE_URL;
const api = axios.create({
    baseURL
});

const isTokenExpired = (token) => {
    if (!token) return true;
    const tokenParts = token.split('.');
    if (tokenParts.length !== 3) return true;
    const payload = JSON.parse(atob(tokenParts[1]));
    const exp = payload.exp;
    return Date.now() >= exp * 1000;
};

api.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token && !isTokenExpired(token)) {
            config.headers.Authorization = `Bearer ${token}`;
        } else {
            localStorage.removeItem('token');
            window.location.replace('http://localhost:3000'); // Redirect to login page
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Token is invalid or expired
            localStorage.removeItem('token');
            window.location.replace('http://localhost:3000'); // Redirect to login page
        }
        return Promise.reject(error);
    }
);

export default api;
