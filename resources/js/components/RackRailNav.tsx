import React, { useRef } from 'react';
import { Category } from '../types';
import { motion } from 'framer-motion';
import { rackHangerVariants } from '../lib/motion';

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
  totalListingsCount = 0,
}) => {
  const rackRef = useRef<HTMLDivElement>(null);

  // Pad numbers with leading zeros (e.g., 01, 02) to mimic physical hanger rail indexing
  const formatHangerIndex = (idx: number) => {
    return idx < 10 ? `0${idx}` : `${idx}`;
  };

  return (
    <nav className="rack-wrap" aria-label="Apparel category rail navigation">
      {/* Rail Label / Identity Indicator */}
      <div className="rack-label">
        <span className="rack-mark" aria-hidden="true" />
        <span className="font-medium tracking-wider">Garment Rail</span>
      </div>

      {/* Horizontal Scrollable Rack */}
      <div
        ref={rackRef}
        className="rack select-none"
        role="tablist"
      >
        {/* Slot 00 / 01: All Pieces */}
        <motion.button
          type="button"
          role="tab"
          aria-selected={selectedCategory === null}
          variants={rackHangerVariants}
          initial="initial"
          whileHover="hover"
          whileTap="active"
          onClick={() => onSelectCategory(null)}
          className={`rack-item text-left ${selectedCategory === null ? 'selected' : ''}`}
        >
          <div className="hanger-number">01</div>
          <div className="rack-name">All Pieces</div>
          <div className="rack-count">
            {totalListingsCount > 0 ? `${totalListingsCount} items` : 'Archive'}
          </div>
        </motion.button>

        {/* Dynamic Category Hangers */}
        {categories.map((cat, index) => {
          const isSelected = selectedCategory === cat.slug;
          const hangerIndex = formatHangerIndex(index + 2);
          const itemCount = cat.products_count ?? (isSelected ? 'Viewing' : 'Curated');

          return (
            <motion.button
              key={cat.id}
              type="button"
              role="tab"
              aria-selected={isSelected}
              variants={rackHangerVariants}
              initial="initial"
              whileHover="hover"
              whileTap="active"
              onClick={() => onSelectCategory(cat.slug)}
              className={`rack-item text-left ${isSelected ? 'selected' : ''}`}
            >
              <div className="hanger-number">{hangerIndex}</div>
              <div className="rack-name">{cat.name}</div>
              <div className="rack-count">
                {typeof itemCount === 'number' ? `${itemCount} items` : itemCount}
              </div>
            </motion.button>
          );
        })}
      </div>
    </nav>
  );
};
