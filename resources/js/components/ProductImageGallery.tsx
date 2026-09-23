import React, { useState } from 'react';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination, Zoom, Thumbs } from 'swiper/modules';
import type { Swiper as SwiperType } from 'swiper';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/zoom';
import 'swiper/css/thumbs';
import { ProductImage } from '../types';

interface ProductImageGalleryProps {
  images: string[];
  galleryImages?: ProductImage[];
  productName: string;
}

export const ProductImageGallery: React.FC<ProductImageGalleryProps> = ({
  images,
  galleryImages,
  productName,
}) => {
  const [thumbsSwiper, setThumbsSwiper] = useState<SwiperType | null>(null);

  const allImages =
    galleryImages && galleryImages.length > 0
      ? galleryImages.map((g) => g.url)
      : images && images.length > 0
      ? images
      : ['https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=1000&q=85'];

  return (
    <div className="flex flex-col gap-3 w-full">
      {/* Bleed-to-edge product photography frame */}
      <div className="relative overflow-hidden bg-[#F0EEE9] border border-[#E8E6E1]">
        <Swiper
          modules={[Navigation, Pagination, Zoom, Thumbs]}
          navigation
          pagination={{ clickable: true }}
          zoom={{ maxRatio: 3 }}
          thumbs={{ swiper: thumbsSwiper && !thumbsSwiper.destroyed ? thumbsSwiper : null }}
          className="aspect-[0.92] w-full"
        >
          {allImages.map((url, idx) => (
            <SwiperSlide key={idx} className="flex items-center justify-center bg-[#F0EEE9]">
              <div className="swiper-zoom-container w-full h-full flex items-center justify-center cursor-zoom-in">
                <img
                  src={url}
                  alt={`${productName} archive view ${idx + 1}`}
                  className="w-full h-full object-cover select-none"
                  loading={idx === 0 ? 'eager' : 'lazy'}
                />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>

        <div className="absolute bottom-3 right-3 z-10 pointer-events-none bg-black/60 px-2.5 py-1 text-[10px] text-white font-mono tracking-widest uppercase">
          Pinch or Double-Tap to Inspect
        </div>
      </div>

      {/* Subtle Hairline Thumbnails */}
      {allImages.length > 1 && (
        <Swiper
          onSwiper={setThumbsSwiper}
          modules={[Thumbs]}
          spaceBetween={8}
          slidesPerView={4}
          watchSlidesProgress
          className="w-full cursor-pointer mt-1"
        >
          {allImages.map((url, idx) => (
            <SwiperSlide
              key={idx}
              className="border border-[#E8E6E1] overflow-hidden focus:outline-none transition-all [&.swiper-slide-thumb-active]:border-[#FF5A36] [&.swiper-slide-thumb-active]:border-2"
            >
              <div className="aspect-square w-full bg-[#F0EEE9]">
                <img
                  src={url}
                  alt={`Thumbnail ${idx + 1}`}
                  className="w-full h-full object-cover opacity-85 hover:opacity-100 transition-opacity"
                />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>
      )}
    </div>
  );
};
