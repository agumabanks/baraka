import { useMemo, useState } from 'react';
import type { PricingTier } from '../../types/home';
import './home.css';

interface PricingSectionProps {
  tiers: PricingTier[];
  currency?: string;
}

const PricingSection = ({ tiers, currency = '$' }: PricingSectionProps) => {
  const [activeTierId, setActiveTierId] = useState<PricingTier['id']>(tiers[0]?.id ?? 'same_day');

  const activeTier = useMemo(
    () => tiers.find((tier) => tier.id === activeTierId) ?? tiers[0],
    [tiers, activeTierId]
  );

  if (!tiers.length) {
    return null;
  }

  return (
    <section className="home-section" id="pricing">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Pricing</h2>
          <p className="section-lead">Transparent rates that make planning effortless.</p>
        </header>

        <div className="pricing-tabs" role="tablist" aria-label="Delivery pricing tiers">
          {tiers.map((tier) => (
            <button
              key={tier.id}
              type="button"
              role="tab"
              aria-selected={tier.id === activeTierId}
              aria-controls={`pricing-panel-${tier.id}`}
              id={`pricing-tab-${tier.id}`}
              className={`pricing-tab ${tier.id === activeTierId ? 'pricing-tab-active' : ''}`}
              onClick={() => setActiveTierId(tier.id)}
            >
              {tier.label}
            </button>
          ))}
        </div>

        {activeTier && (
          <div
            id={`pricing-panel-${activeTier.id}`}
            role="tabpanel"
            aria-labelledby={`pricing-tab-${activeTier.id}`}
            className="pricing-panel"
          >
            <div className="grid pricing-grid">
              {activeTier.rates.map((rate) => (
                <article key={rate.id} className="pricing-card">
                  <p className="pricing-weight">{rate.weight}</p>
                  <h3 className="pricing-price">
                    <span className="sr-only">Price: </span>
                    {currency}
                    {rate.price.toFixed(2)}
                  </h3>
                  <p className="pricing-category">{rate.category}</p>
                </article>
              ))}
            </div>
          </div>
        )}
      </div>
    </section>
  );
};

export default PricingSection;
