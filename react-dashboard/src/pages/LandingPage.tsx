import { useNavigate } from 'react-router-dom';
import { useCallback } from 'react';
import Home from './Home';
import '../styles/landing.css';

const LandingPage = () => {
  const navigate = useNavigate();

  const handleLogin = useCallback(() => {
    navigate('/login');
  }, [navigate]);

  const handleRegister = useCallback(() => {
    navigate('/register');
  }, [navigate]);

  return (
    <div className="landing-page-wrapper">
      {/* Landing Header */}
      <header className="landing-header">
        <div className="landing-header-content">
          <div className="landing-logo-section">
            <img src="/images/default/logo1.png" alt="Baraka Courier" className="landing-logo" />
            <span className="landing-brand-name">Baraka Courier</span>
          </div>
          <nav className="landing-nav">
            <button onClick={handleLogin} className="landing-nav-link login-link">
              Sign In
            </button>
            <button onClick={handleRegister} className="landing-nav-link register-link">
              Get Started
            </button>
          </nav>
        </div>
      </header>

      {/* Home Content */}
      <main className="landing-main">
        <Home />
      </main>

      {/* Landing Footer */}
      <footer className="landing-footer">
        <div className="landing-footer-content">
          <div className="footer-section">
            <h3 className="footer-title">About Baraka</h3>
            <p className="footer-text">Leading logistics and courier services in East Africa.</p>
          </div>
          <div className="footer-section">
            <h3 className="footer-title">Quick Links</h3>
            <ul className="footer-links">
              <li><button className="footer-link" onClick={handleLogin}>Sign In</button></li>
              <li><button className="footer-link" onClick={handleRegister}>Register</button></li>
              <li><a href="#services" className="footer-link">Services</a></li>
              <li><a href="#contact" className="footer-link">Contact</a></li>
            </ul>
          </div>
          <div className="footer-section">
            <h3 className="footer-title">Legal</h3>
            <ul className="footer-links">
              <li><a href="#privacy" className="footer-link">Privacy Policy</a></li>
              <li><a href="#terms" className="footer-link">Terms of Service</a></li>
            </ul>
          </div>
          <div className="footer-section">
            <h3 className="footer-title">Connect</h3>
            <ul className="footer-links">
              <li><a href="#" className="footer-link">Twitter</a></li>
              <li><a href="#" className="footer-link">LinkedIn</a></li>
              <li><a href="#" className="footer-link">Facebook</a></li>
            </ul>
          </div>
        </div>
        <div className="footer-bottom">
          <p>&copy; 2025 Baraka Courier. All rights reserved.</p>
        </div>
      </footer>
    </div>
  );
};

export default LandingPage;
