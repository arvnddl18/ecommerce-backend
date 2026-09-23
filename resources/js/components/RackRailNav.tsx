import React, { useRef } from 'react';
import { Category } from '../types';

interface RackRailNavProps {
  categories: Category[];
  selectedCategory: string | null;
  onSelectCategory: (slug: string | null) => void;
  totalListingsCount?: number;
}

export const RackRailNav: React.FC<RackRailNavProps> = ({
  categories,
  selectedCategory,
  onSelectCategory,
  totalListingsCount = 48,
}) => {
  const rackRef = useRef<HTMLDivElement>(null);

  const formatCount = (count?: number) => {
    if (count === undefined || count === null) return '09';
    return count < 10 ? `0${count}` : `${count}`;
  };

  return (
    <section className="rack-wrap" aria-label="Shop by category">
      <div className="rack-label">
        <span className="rack-mark" aria-hidden="true" />
        <span>Shop by category</span>
      </div>

      <div
        ref={rackRef}
        className="rack"
        tabIndex={0}
        role="tablist"
      >
        <button
          type="button"
          role="tab"
          aria-selected={selectedCategory === null}
          onClick={() => {
            onSelectCategory(null);
            const el = document.getElementById('products');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
          }}
          className={`rack-item ${selectedCategory === null ? 'selected' : ''}`}
        >
          <span>All pieces</span>
          <small>{formatCount(totalListingsCount)}</small>
        </button>

        {categories.map((cat) => {
          const isSelected = selectedCategory === cat.slug;
          return (
            <button
              key={cat.id}
              type="button"
              role="tab"
              aria-selected={isSelected}
              onClick={() => {
                onSelectCategory(cat.slug);
                const el = document.getElementById('products');
                if (el) el.scrollIntoView({ behavior: 'smooth' });
              }}
              className={`rack-item ${isSelected ? 'selected' : ''}`}
            >
              <span>{cat.name}</span>
              <small>{formatCount(cat.products_count)}</small>
            </button>
          );
        })}
      </div>
    </section>
  );
};
