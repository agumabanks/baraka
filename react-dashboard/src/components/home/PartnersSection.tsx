import type { PartnerItem } from '../../types/home';
import './home.css';

interface PartnersSectionProps {
  partners: PartnerItem[];
}

const PartnersSection = ({ partners }: PartnersSectionProps) => {
  if (!partners.length) {
    return null;
  }

  return (
    <section className="home-section">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Our Partners</h2>
          <p className="section-lead">Trusted brands that rely on our delivery network.</p>
        </header>
        <div className="partner-track" role="list">
          {partners.map((partner) => (
            <a
              key={partner.id}
              href={partner.link}
              className="partner-card"
              role="listitem"
            >
              <img src={partner.imageUrl} alt={partner.name} className="partner-image" />
            </a>
          ))}
        </div>
      </div>
    </section>
  );
};

export default PartnersSection;
