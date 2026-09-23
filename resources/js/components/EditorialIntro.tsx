import React from 'react';

interface EditorialIntroProps {
  onExploreClick: () => void;
}

export const EditorialIntro: React.FC<EditorialIntroProps> = ({ onExploreClick }) => {
  return (
    <section className="intro-section" aria-labelledby="intro-headline">
      {/* Primary Editorial Column */}
      <div className="max-w-2xl">
        <p className="kicker">Autumn / Winter 2026 Archive</p>
        <h1 id="intro-headline">
          The Tactile <em>Wardrobe</em>
        </h1>
        <p className="intro-text">
          An intentional collective of independent apparel makers, functional garments, and limited seasonal runs. Built with atomic inventory locking, decentralized vendor payouts, and verified maker authenticity.
        </p>

        <button
          type="button"
          onClick={onExploreClick}
          className="text-link bg-transparent border-0 cursor-pointer"
        >
          <span>Explore The Garment Rail</span>
          <span aria-hidden="true">+</span>
        </button>
      </div>

      {/* Editorial Aside Note */}
      <div className="intro-note">
        <span className="note-line" aria-hidden="true" />
        <div className="space-y-1">
          <p className="font-medium text-[#1A1A1A]">Direct from independent ateliers.</p>
          <p>Decentralized fulfillment & verified craft.</p>
          <p className="text-[11px] opacity-75">Multi-Vendor Stripe Connect settlement.</p>
        </div>
      </div>
    </section>
  );
};
