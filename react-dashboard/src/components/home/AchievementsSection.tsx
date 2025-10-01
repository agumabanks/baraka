import { useEffect, useState } from 'react';
import type { AchievementItem } from '../../types/home';
import './home.css';

interface AchievementsSectionProps {
  items: AchievementItem[];
}

const formatNumber = (value: number) => {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1).replace(/\.0$/, '')}M`;
  }

  if (value >= 1000) {
    return `${(value / 1000).toFixed(1).replace(/\.0$/, '')}K`;
  }

  return value.toString();
};

const AnimatedValue = ({ value, suffix }: { value: number; suffix?: string }) => {
  const [displayValue, setDisplayValue] = useState(0);

  useEffect(() => {
    let frame = 0;
    const totalFrames = 24;
    const increment = value / totalFrames;

    const tick = () => {
      frame += 1;
      if (frame >= totalFrames) {
        setDisplayValue(value);
        return;
      }
      setDisplayValue((prev) => Math.min(value, prev + increment));
      requestAnimationFrame(tick);
    };

    tick();
  }, [value]);

  return (
    <span>
      {formatNumber(Math.round(displayValue))}
      {suffix ?? ''}
    </span>
  );
};

const AchievementsSection = ({ items }: AchievementsSectionProps) => {
  if (!items.length) {
    return null;
  }

  return (
    <section className="home-section achievements-section">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Happy Achievements</h2>
          <p className="section-lead">Milestones that reflect our dedication to better deliveries.</p>
        </header>
        <div className="grid achievements-grid">
          {items.map((item) => (
            <article key={item.id} className="achievement-card">
              <div className="achievement-icon" aria-hidden="true">
                <i className={item.icon} />
              </div>
              <p className="achievement-value" aria-live="polite">
                <AnimatedValue value={item.value} suffix={item.suffix} />
              </p>
              <p className="achievement-label">{item.label}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default AchievementsSection;
