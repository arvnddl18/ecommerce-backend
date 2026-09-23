import React from 'react';
import { ArrowRight, ShieldCheck, Zap, Server, Database } from 'lucide-react';

export const HeroBanner: React.FC<{ onExploreClick: () => void }> = ({ onExploreClick }) => {
  return (
    <section className="relative overflow-hidden pt-8 pb-12 sm:pt-14 sm:pb-18 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border-b border-slate-800/60">
      {/* Decorative ambient glowing orbs */}
      <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute top-1/3 right-10 w-72 h-72 bg-violet-600/10 rounded-full blur-3xl pointer-events-none" />

      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {/* Technology Stack Pill Badge */}
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900/90 border border-indigo-500/30 text-xs font-medium text-indigo-300 shadow-inner mb-6 backdrop-blur-md">
          <span className="flex h-2 w-2 rounded-full bg-emerald-400 animate-ping" />
          <span>PostgreSQL 16 · Redis 7 · Stripe Webhooks · Docker</span>
        </div>

        {/* Main Headline */}
        <h1 className="text-4xl sm:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-[1.15]">
          Engineering Next-Gen Audio & Hardware Commerce
        </h1>

        <p className="mt-5 text-base sm:text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
          Demonstrating high-performance, containerized full-stack architecture with decoupled React 18 frontend,
          atomic inventory locking, and end-to-end idempotent Stripe checkout.
        </p>

        {/* CTA Buttons */}
        <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
          <button
            onClick={onExploreClick}
            className="flex items-center gap-2.5 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 hover:shadow-indigo-600/50 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer"
          >
            <span>Explore Hardware Catalog</span>
            <ArrowRight className="w-4 h-4" />
          </button>
          <a
            href="https://github.com/arvnddl18/ecommerce-backend"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-700/80 font-medium text-sm transition-all"
          >
            <span>View Architecture on GitHub</span>
          </a>
        </div>

        {/* Feature Highlights Grid */}
        <div className="mt-14 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-5xl mx-auto text-left">
          <div className="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
            <Server className="w-5 h-5 text-indigo-400 mb-2" />
            <h3 className="text-sm font-semibold text-white">Stateless REST API</h3>
            <p className="text-xs text-slate-400 mt-1">Laravel 11 backend with Sanctum token authentication.</p>
          </div>
          <div className="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
            <Zap className="w-5 h-5 text-amber-400 mb-2" />
            <h3 className="text-sm font-semibold text-white">Redis 7 Acceleration</h3>
            <p className="text-xs text-slate-400 mt-1">Sub-millisecond cart store & cache-aside catalog retrieval.</p>
          </div>
          <div className="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
            <ShieldCheck className="w-5 h-5 text-emerald-400 mb-2" />
            <h3 className="text-sm font-semibold text-white">Idempotent Webhooks</h3>
            <p className="text-xs text-slate-400 mt-1">HMAC SHA-256 signature verification & event deduplication.</p>
          </div>
          <div className="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-sm">
            <Database className="w-5 h-5 text-cyan-400 mb-2" />
            <h3 className="text-sm font-semibold text-white">ACID Integrity</h3>
            <p className="text-xs text-slate-400 mt-1">PostgreSQL 16 relational data store with zero data race.</p>
          </div>
        </div>
      </div>
    </section>
  );
};
