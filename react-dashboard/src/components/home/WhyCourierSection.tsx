import type { WhyCourierItem } from '../../types/home';
import './home.css';

interface WhyCourierSectionProps {
  items: WhyCourierItem[];
  companyName?: string;
}

const WhyCourierSection = ({ items, companyName = 'Our Courier' }: WhyCourierSectionProps) => {
  if (!items.length) {
    return null;
  }

  return (
    <section className="home-section">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Why {companyName}</h2>
          <p className="section-lead">The essential promises that keep our deliveries personal and precise.</p>
        </header>
        <div className="grid why-grid">
          {items.map((item) => (
            <article key={item.id} className="why-card">
              <div className="why-visual" aria-hidden="true">
                <img src={item.imageUrl} alt="" className="why-image" />
              </div>
              <h3 className="card-title text-center">{item.title}</h3>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default WhyCourierSection;
