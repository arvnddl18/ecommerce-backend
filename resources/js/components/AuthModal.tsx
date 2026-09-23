import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import { motion, AnimatePresence } from 'framer-motion';
import { modalEntranceVariants } from '../lib/motion';

interface AuthModalProps {
  isOpen: boolean;
  onClose: () => void;
}

export const AuthModal: React.FC<AuthModalProps> = ({ isOpen, onClose }) => {
  const { login, register } = useAuth();
  const [isRegister, setIsRegister] = useState(false);
  const [role, setRole] = useState<'buyer' | 'seller'>('buyer');
  const [name, setName] = useState('');
  const [storeName, setStoreName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [ageAcknowledged, setAgeAcknowledged] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (isRegister && !ageAcknowledged) {
      setError('Compliance gate: You must acknowledge being 16 years of age or older.');
      return;
    }

    setIsSubmitting(true);

    try {
      if (isRegister) {
        await register(
          name,
          email,
          password,
          role,
          ageAcknowledged,
          role === 'seller' ? storeName : undefined
        );
      } else {
        await login(email, password);
      }
      onClose();
    } catch (err: any) {
      setError(err.message || 'Authentication error occurred.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AnimatePresence>
      <div
        className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
      >
        <motion.div
          variants={modalEntranceVariants}
          initial="hidden"
          animate="visible"
          exit="exit"
          onClick={(e) => e.stopPropagation()}
          className="relative w-full max-w-md bg-[#FAFAF8] text-[#1A1A1A] border border-[#E8E6E1] shadow-2xl p-6 sm:p-8"
          role="dialog"
          aria-modal="true"
        >
          {/* Close button */}
          <button
            type="button"
            onClick={onClose}
            className="absolute top-4 right-4 w-7 h-7 rounded-full border border-[#E8E6E1] bg-white text-[#1A1A1A] hover:bg-[#1A1A1A] hover:text-white flex items-center justify-center text-xs font-mono transition-colors cursor-pointer"
            aria-label="Close"
          >
            ✕
          </button>

          {/* Heading */}
          <div className="mb-6">
            <span className="text-[10px] font-mono uppercase tracking-widest text-[#FF5A36] block mb-1">
              {isRegister ? 'New Account Registration' : 'Account Access'}
            </span>
            <h2 className="text-2xl font-serif font-medium text-[#1A1A1A]">
              {isRegister ? 'Join The Collective' : 'Sign in to Maison'}
            </h2>
            <p className="text-xs text-[#6B6B6B] mt-1">
              {isRegister
                ? 'Register as an independent shopper or apply as an apparel seller.'
                : 'Access your order history, verified reviews, and merchant studio.'}
            </p>
          </div>

          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-xs text-[#D14343]">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            {isRegister && (
              <>
                {/* Role Switcher */}
                <div>
                  <label className="block text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-1.5">
                    Account Type
                  </label>
                  <div className="grid grid-cols-2 gap-2 text-xs">
                    <button
                      type="button"
                      onClick={() => setRole('buyer')}
                      className={`p-2.5 text-center border transition-colors cursor-pointer ${
                        role === 'buyer'
                          ? 'border-[#1A1A1A] bg-white text-[#1A1A1A] font-semibold'
                          : 'border-[#E8E6E1] bg-[#FAFAF8] text-[#6B6B6B]'
                      }`}
                    >
                      Buyer / Collector
                    </button>
                    <button
                      type="button"
                      onClick={() => setRole('seller')}
                      className={`p-2.5 text-center border transition-colors cursor-pointer ${
                        role === 'seller'
                          ? 'border-[#1A1A1A] bg-white text-[#1A1A1A] font-semibold'
                          : 'border-[#E8E6E1] bg-[#FAFAF8] text-[#6B6B6B]'
                      }`}
                    >
                      Apparel Atelier
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-1">
                    Your Name
                  </label>
                  <input
                    type="text"
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="Jean-Paul"
                    className="w-full p-2.5 bg-white border border-[#E8E6E1] text-xs text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                  />
                </div>

                {role === 'seller' && (
                  <div>
                    <label className="block text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-1">
                      Atelier / Store Name
                    </label>
                    <input
                      type="text"
                      required
                      value={storeName}
                      onChange={(e) => setStoreName(e.target.value)}
                      placeholder="Atelier Studio Paris"
                      className="w-full p-2.5 bg-white border border-[#E8E6E1] text-xs text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
                    />
                  </div>
                )}
              </>
            )}

            <div>
              <label className="block text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-1">
                Email Address
              </label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="collector@maison.com"
                className="w-full p-2.5 bg-white border border-[#E8E6E1] text-xs text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
              />
            </div>

            <div>
              <label className="block text-[10px] font-bold uppercase tracking-wider text-[#6B6B6B] mb-1">
                Password
              </label>
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                className="w-full p-2.5 bg-white border border-[#E8E6E1] text-xs text-[#1A1A1A] focus:outline-none focus:border-[#1A1A1A]"
              />
            </div>

            {/* Age Gate Acknowledgment */}
            {isRegister && (
              <label className="flex items-start gap-2 pt-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  checked={ageAcknowledged}
                  onChange={(e) => setAgeAcknowledged(e.target.checked)}
                  className="mt-0.5 accent-[#FF5A36]"
                />
                <span className="text-[11px] text-[#6B6B6B] leading-tight">
                  I confirm that I am 16 years of age or older in accordance with marketplace terms and minor safety requirements.
                </span>
              </label>
            )}

            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full py-3 bg-[#FF5A36] hover:bg-[#E64A28] active:bg-[#CC3F20] text-white font-bold text-xs uppercase tracking-widest transition-colors cursor-pointer mt-4"
            >
              {isSubmitting
                ? 'Processing...'
                : isRegister
                ? 'Create Account'
                : 'Sign In'}
            </button>
          </form>

          {/* Toggle between Login and Register */}
          <div className="mt-6 pt-4 border-t border-[#E8E6E1] text-center text-xs text-[#6B6B6B]">
            {isRegister ? (
              <p>
                Already have an account?{' '}
                <button
                  type="button"
                  onClick={() => {
                    setIsRegister(false);
                    setError(null);
                  }}
                  className="text-[#1A1A1A] font-semibold border-b border-[#1A1A1A] cursor-pointer"
                >
                  Sign in
                </button>
              </p>
            ) : (
              <p>
                New to the marketplace?{' '}
                <button
                  type="button"
                  onClick={() => {
                    setIsRegister(true);
                    setError(null);
                  }}
                  className="text-[#FF5A36] font-semibold border-b border-[#FF5A36] cursor-pointer"
                >
                  Create account (16+)
                </button>
              </p>
            )}
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
};
