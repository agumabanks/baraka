import type { ServiceItem } from '../../types/home';
import './home.css';

interface ServicesSectionProps {
  services: ServiceItem[];
}

const ServicesSection = ({ services }: ServicesSectionProps) => {
  if (!services.length) {
    return null;
  }

  return (
    <section className="home-section">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Our Services</h2>
          <p className="section-lead">
            Focused, reliable delivery options designed for every business rhythm.
          </p>
        </header>
        <div className="grid services-grid">
          {services.map((service) => (
            <article key={service.id} className="service-card">
              <div className="service-visual" aria-hidden="true">
                <img src={service.imageUrl} alt="" className="service-image" />
              </div>
              <div className="service-copy">
                <h3 className="card-title">{service.title}</h3>
                <p className="card-text">{service.description}</p>
                <a className="text-link" href={service.link}>
                  Explore
                </a>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default ServicesSection;
