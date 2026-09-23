import { Variants } from 'framer-motion';

// Check for reduced motion preference
export const isReducedMotion = (): boolean => {
  if (typeof window === 'undefined') return false;
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
};

/**
 * Signature Motion 1: Rack-Rail Category Glide
 * Restrained, tactile momentum for category hanger browsing
 */
export const rackHangerVariants: Variants = {
  initial: {
    opacity: 0.9,
    y: 0,
  },
  hover: {
    y: -2,
    transition: {
      duration: 0.18,
      ease: [0.25, 1, 0.5, 1],
    },
  },
  active: {
    y: 0,
    transition: {
      duration: 0.12,
    },
  },
};

/**
 * Signature Motion 2: "Pull to Inspect" Product Zoom
 * Direct response to user interaction (hover/pan/drag inspection)
 */
export const pullToInspectVariants: Variants = {
  rest: {
    scale: 1,
    transition: {
      duration: 0.4,
      ease: [0.16, 1, 0.3, 1],
    },
  },
  inspect: {
    scale: 1.06,
    transition: {
      duration: 0.35,
      ease: [0.16, 1, 0.3, 1],
    },
  },
};

/**
 * Functional Micro-Interaction: Add to Cart Confirmation
 * 150-250ms immediate feedback
 */
export const quickAddFeedbackVariants: Variants = {
  initial: { scale: 1 },
  tap: { scale: 0.96 },
  success: {
    scale: [1, 1.04, 1],
    transition: { duration: 0.22, ease: 'easeOut' },
  },
};

/**
 * Functional Slide-over: Cart Drawer
 * Crisp slide from right with gentle spring damping
 */
export const cartDrawerVariants: Variants = {
  hidden: {
    x: '100%',
    transition: {
      duration: 0.28,
      ease: [0.32, 0.72, 0, 1],
    },
  },
  visible: {
    x: '0%',
    transition: {
      duration: 0.32,
      ease: [0.16, 1, 0.3, 1],
    },
  },
  exit: {
    x: '100%',
    transition: {
      duration: 0.24,
      ease: [0.32, 0.72, 0, 1],
    },
  },
};

/**
 * Modal Entrance
 * Subtle emergence for detail view and modals
 */
export const modalEntranceVariants: Variants = {
  hidden: {
    opacity: 0,
    scale: 0.98,
    y: 10,
    transition: { duration: 0.18 },
  },
  visible: {
    opacity: 1,
    scale: 1,
    y: 0,
    transition: {
      duration: 0.24,
      ease: [0.16, 1, 0.3, 1],
    },
  },
  exit: {
    opacity: 0,
    scale: 0.98,
    y: 8,
    transition: { duration: 0.18 },
  },
};
