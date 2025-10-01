import { FormEvent, useState } from 'react';
import type { BannerContent } from '../../types/home';
import './home.css';

interface BannerSectionProps {
  content: BannerContent;
  onTrack?: (trackingId: string) => void;
}

const BannerSection = ({ content, onTrack }: BannerSectionProps) => {
  const [trackingId, setTrackingId] = useState('');
  const [submittedId, setSubmittedId] = useState<string | null>(null);

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!trackingId.trim()) {
      return;
    }

    setSubmittedId(trackingId.trim());
    onTrack?.(trackingId.trim());
    setTrackingId('');
  };

  return (
    <section className="home-section home-banner">
      <div className="home-container banner-grid">
        <div className="banner-copy">
          <div className="banner-title" aria-live="polite">
            {content.titleSegments.map((segment, index) => (
              <span
                key={`${segment.text}-${index}`}
                className={segment.highlight ? 'banner-title-highlight' : undefined}
              >
                {segment.text}
              </span>
            ))}
          </div>
          <p className="banner-subtitle">{content.subtitle}</p>
          <form className="tracking-form" onSubmit={handleSubmit}>
            <label htmlFor="tracking-id" className="sr-only">
              {content.trackingPlaceholder}
            </label>
            <input
              id="tracking-id"
              type="text"
              value={trackingId}
              onChange={(event) => setTrackingId(event.target.value)}
              placeholder={content.trackingPlaceholder}
              className="tracking-input"
            />
            <button type="submit" className="tracking-button">
              {content.trackingButtonLabel}
            </button>
          </form>
          {submittedId && (
            <p className="tracking-feedback" role="status">
              Tracking lookup started for <span>{submittedId}</span>
            </p>
          )}
        </div>
        {content.imageUrl && (
          <div className="banner-visual" aria-hidden="true">
            <img src={content.imageUrl} alt="" className="banner-image" />
          </div>
        )}
      </div>
    </section>
  );
};

export default BannerSection;
