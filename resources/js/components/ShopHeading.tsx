import React from 'react';

interface ShopHeadingProps {
  currentSort: string;
  onSortChange: (sort: string) => void;
  eyebrow?: string;
  title?: string;
}

export const ShopHeading: React.FC<ShopHeadingProps> = ({
  currentSort,
  onSortChange,
  eyebrow = 'New arrivals',
  title = 'Shop the latest',
}) => {
  const getSortLabel = (val: string) => {
    switch (val) {
      case 'price_asc':
        return 'Price: Low to High';
      case 'price_desc':
        return 'Price: High to Low';
      case 'name_asc':
        return 'Alphabetical';
      case 'newest':
      default:
        return 'Featured';
    }
  };

  return (
    <section className="shop-heading" aria-labelledby="shop-heading-title">
      <div>
        <p className="eyebrow">{eyebrow}</p>
        <h1 id="shop-heading-title">{title}</h1>
      </div>

      <div className="heading-right">
        <p>
          Fresh pieces, ready to wear.<br />Updated weekly.
        </p>

        <div className="relative inline-block">
          <select
            value={currentSort}
            onChange={(e) => onSortChange(e.target.value)}
            className="sort-link appearance-none pr-5 focus:outline-none"
            aria-label="Sort products"
          >
            <option value="newest">Sort: Featured</option>
            <option value="price_asc">Sort: Price: Low to High</option>
            <option value="price_desc">Sort: Price: High to Low</option>
            <option value="name_asc">Sort: Alphabetical</option>
          </select>
          <span className="pointer-events-none absolute right-0 bottom-2 text-xs" aria-hidden="true">
            ⌄
          </span>
        </div>
      </div>
    </section>
  );
};
