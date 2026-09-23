---
name: obsidian-memory
description: Autonomous Obsidian memory brain protocol for continuous learning, targeted context retrieval, and progressive knowledge consolidation in the project's external Obsidian vault.
---

# Obsidian Autonomous Memory Brain

This skill defines the autonomous memory system using the project's Obsidian vault.

Obsidian is not merely documentation.
*Obsidian is the project's external memory, knowledge base, and evolving cognitive context.*

The objective is:
**Chat → Understand → Extract → Store → Connect → Retrieve → Apply → Refine**

---

## 1. Hierarchy of Truth

```
CURRENT USER INSTRUCTION
        ↓
ACTUAL PROJECT FILES / SOURCE CODE
        ↓
PROJECT CONFIGURATION
        ↓
OBSIDIAN MEMORY (Vault/)
        ↓
PROJECT DOCUMENTATION
        ↓
RELEVANT CHAT HISTORY
        ↓
GENERAL KNOWLEDGE
```

The repository source code is **ALWAYS** authoritative over memory notes.
- Obsidian tells you: *"What has been decided, learned, preferred, or discovered."*
- The repository tells you: *"What currently exists."*
- The current user request tells you: *"What should happen now."*

Never allow memory notes to override verified code.

---

## 2. Vault Structure (`Vault/ECOMMERCE VAULT/`)

The persistent memory is organized into structured categories inside `Vault/ECOMMERCE VAULT/`:

- `00 - Core Memory/CORE_MEMORY.md`: Central high-level index of the entire memory brain.
- `01 - Project Memory/`: High-level domain and project overviews.
- `02 - Architecture/`: System topology, data flow, and runtime mechanics.
- `03 - Decisions/`: Architecture Decision Records (ADRs) with rationale.
- `04 - Requirements/`: Functional and non-functional specifications.
- `05 - User Preferences/`: Developer conventions, UI/UX aesthetics, and coding rules.
- `06 - Technical Knowledge/`: Domain expertise (Stripe, PostgreSQL JSONB, Redis queues, Docker OPcache).
- `07 - Problems & Solutions/`: Root causes, verified fixes, and failed approaches to avoid.
- `08 - Completed Work/`: Completed milestones, feature logs, and migrations.
- `09 - Current State/`: Active tasks, blockers, and next logical steps.
- `10 - Temporary/`: Transient scratchpads and in-flight investigations.
- `99 - Archive/`: Superseded or obsolete records preserved for historical context.

---

## 3. Targeted Retrieval (Token-Efficient Recall)

Before answering or modifying code:
1. Identify the task and necessary domain context.
2. Search `Vault/` using semantic meaning (e.g., searching for "stripe webhook" inspects payment pipeline and webhook verification notes).
3. Retrieve **only** the relevant notes.
4. **Never load the entire vault into context.**
5. Cross-reference retrieved memory against active repository files.

---

## 4. Silent Memory Extraction & Consolidation

After every meaningful interaction, silently evaluate:
- What new information was learned?
- What existing information was clarified or changed?
- What became obsolete?
- What will future tasks need to know?

### Progressive Updating Rules:
- **Consolidate existing notes** instead of creating duplicate fragments.
- Mark confidence states:
  - `VERIFIED`: Confirmed by actual repository files.
  - `INFERRED`: Derived from patterns but unconfirmed.
  - `PROPOSED`: Planned or proposed design.
  - `HISTORICAL`: Previously true, now superseded.
  - `OBSOLETE`: No longer applicable.
- Use `[[WikiLinks]]` to connect related concepts into a navigable knowledge graph.

---

## 5. Security & Privacy Guardrail

**NEVER** store credentials in memory:
- No passwords, private keys, API secrets, or HMAC keys.
- Store references to environment variable keys (e.g., `STRIPE_WEBHOOK_SECRET`) rather than the raw secret value.
