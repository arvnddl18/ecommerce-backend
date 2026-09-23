import React from 'react';
import { ProductVariant } from '../types';
import { motion } from 'framer-motion';

interface VariantSelectorProps {
  variants: ProductVariant[];
  selectedVariant: ProductVariant | null;
  onSelectVariant: (variant: ProductVariant) => void;
}

export const VariantSelector: React.FC<VariantSelectorProps> = ({
  variants,
  selectedVariant,
  onSelectVariant,
}) => {
  if (!variants || variants.length === 0) {
    return null;
  }

  const sizes = Array.from(new Set(variants.map((v) => v.size)));
  const colors = Array.from(new Set(variants.map((v) => v.color)));

  const currentSize = selectedVariant?.size || sizes[0];
  const currentColor = selectedVariant?.color || colors[0];

  const handleSizeChange = (size: string) => {
    const match =
      variants.find((v) => v.size === size && v.color === currentColor) ||
      variants.find((v) => v.size === size);
    if (match) {
      onSelectVariant(match);
    }
  };

  const handleColorChange = (color: string) => {
    const match =
      variants.find((v) => v.size === currentSize && v.color === color) ||
      variants.find((v) => v.color === color);
    if (match) {
      onSelectVariant(match);
    }
  };

  const isLowStock =
    selectedVariant &&
    selectedVariant.stock_quantity > 0 &&
    selectedVariant.stock_quantity <= 5;
  const isOutOfStock = selectedVariant && selectedVariant.stock_quantity === 0;

  return (
    <div className="space-y-4 py-4 border-t border-[#E8E6E1]">
      {/* Size Selection */}
      <div>
        <div className="flex items-center justify-between mb-2">
          <span className="text-[11px] font-bold uppercase tracking-wider text-[#6B6B6B]">
            Size: <span className="text-[#1A1A1A] font-semibold">{currentSize}</span>
          </span>
          <span className="text-[10px] text-[#6B6B6B] border-b border-[#E8E6E1] cursor-pointer hover:text-[#1A1A1A]">
            Measurements
          </span>
        </div>
        <div className="flex flex-wrap gap-2">
          {sizes.map((size) => {
            const isSelected = size === currentSize;
            const matchingVar = variants.find(
              (v) => v.size === size && v.color === currentColor
            );
            const isAvailable = matchingVar ? matchingVar.stock_quantity > 0 : true;

            return (
              <button
                key={size}
                type="button"
                onClick={() => handleSizeChange(size)}
                className={`px-3 py-1.5 text-xs font-mono transition-all border cursor-pointer ${
                  isSelected
                    ? 'border-[#FF5A36] bg-[#FF5A36] text-white'
                    : isAvailable
                    ? 'border-[#E8E6E1] bg-white text-[#1A1A1A] hover:border-[#1A1A1A]'
                    : 'border-[#E8E6E1] bg-[#FAFAF8] text-[#A0A0A0] line-through cursor-not-allowed'
                }`}
              >
                {size}
              </button>
            );
          })}
        </div>
      </div>

      {/* Color Selection */}
      <div>
        <div className="flex items-center justify-between mb-2">
          <span className="text-[11px] font-bold uppercase tracking-wider text-[#6B6B6B]">
            Colorway: <span className="text-[#1A1A1A] font-semibold">{currentColor}</span>
          </span>
        </div>
        <div className="flex flex-wrap gap-2">
          {colors.map((color) => {
            const isSelected = color === currentColor;
            return (
              <button
                key={color}
                type="button"
                onClick={() => handleColorChange(color)}
                className={`px-3 py-1 text-xs transition-all border cursor-pointer ${
                  isSelected
                    ? 'border-[#1A1A1A] bg-[#1A1A1A] text-white font-medium'
                    : 'border-[#E8E6E1] bg-white text-[#1A1A1A] hover:border-[#1A1A1A]'
                }`}
              >
                {color}
              </button>
            );
          })}
        </div>
      </div>

      {/* Stock Signals */}
      {isLowStock ? (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="text-[11px] text-[#CC3F20] font-medium bg-[#FFF5F2] px-2.5 py-1 border-l-2 border-[#FF5A36]"
        >
          Scarcity: Only {selectedVariant.stock_quantity} pieces remaining in this cut.
        </motion.div>
      ) : isOutOfStock ? (
        <div className="text-[11px] text-[#D14343] font-medium bg-[#FFF5F5] px-2.5 py-1 border-l-2 border-[#D14343]">
          Archive edition: Currently out of stock in selected size.
        </div>
      ) : null}
    </div>
  );
};
