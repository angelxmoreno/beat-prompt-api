# BeatPrompt API

BeatPrompt is a web application that translates vague, artist-referential music requests into structured, sanitized prompts that preserve the intended musical qualities without naming or imitating real artists. 

This repository contains the backend API for the MVP, built with **CakePHP** and **PHP**. It orchestrates a multi-phase pipeline using large language models (LLMs) to extract musical style attributes and synthesize policy-safe prompts for tools like Lyria.

## Core Tech Stack

- **CakePHP 5** - Primary application backend and routing
- **cognesy/instructor-php** - Guarantee structured, typed data extraction from LLMs
- **CakeInstructor** - Shared CakePHP integration layer for provider connections, structured extraction, and diagnostics
- **Environment-driven caching** - Pipeline/application-level caching where needed; prompt modules themselves remain stateless

## Pipeline Overview

The API processes requests in four phases:

1. **Phase 0 — Canonicalization:** Uses an LLM structured extractor to normalize free-form intent into a deterministic `CanonicalRequest` plus canonical key.
2. **Phase 1 — Style Extraction:** Infers musical attributes (genre, mood, energy, tempo, instruments) using a high-quality LLM via `instructor-php`.
3. **Phase 2 — Prompt Synthesis:** Converts the extracted style attributes into a polished, Lyria-safe prompt using a rewrite-oriented model.
4. **Phase 3 — Policy Cleaner:** Ensures the final prompt contains no real artist names, song titles, or direct references.

## Getting Started

1. **Install dependencies:**

```bash
composer install
```

2. **Create local config:**

```bash
cp config/app_local.example.php config/app_local.php
```

3. **Create a local environment file:**

```bash
cp sample.env .env
```

Update the values in `.env` for your local setup. At minimum, ensure `SECURITY_SALT` is set. Configure your `CACHE_URL` (e.g., `file:///tmp/cache/` for local dev) and your LLM provider keys (e.g., `OPENAI_API_KEY`) as they are added to the project.

4. **Start the local development server:**

```bash
bin/cake server -p 8080
```

## API Surface

For the MVP, the primary endpoint is:

`POST /api/optimize`

**Request:**
```json
{
  "text": "Joyner Lucas type beat",
  "instrumentalOnly": true
}
```

**Response:**
```json
{
  "input": "Joyner Lucas type beat",
  "normalized": "joyner lucas type beat",
  "canonicalKey": "kind:artist_style_prompt|artists:joyner lucas|target:beat|modifiers:",
  "style": {
    "genre": "lyrical hip-hop / modern boom bap",
    "mood": ["dark", "intense", "cinematic"],
    "energy": "high",
    "tempoBpm": 94,
    "instruments": ["piano", "strings", "hard drums", "bass"]
  },
  "prompt": "Dark cinematic lyrical hip-hop instrumental with hard boom-bap drums, tense piano melodies, dramatic strings, punchy bass, and focused storytelling energy. 94 BPM. No vocals."
}
```

## Useful Commands

- `composer test` - Run the test suite
- `composer stan` - Run PHPStan static analysis
- `composer cs-check` - Run CodeSniffer
- `bin/cake prompt_canonicalize "Joyner Lucas type beat"` - LLM canonicalization test command
- `bin/cake prompt_canonicalize "a beat with vibes like Joyner Lucas" --format=json` - Emit canonicalization output as JSON
- `bin/cake prompt_canonicalize "Joyner Lucas type beat" --connection=default` - Use a specific CakeInstructor connection
- `bin/cake prompt_canonicalize_compare --connection=default` - Run benchmark cases using default `config/prompt-canonicalize-cases.json`
- `bin/cake prompt_canonicalize_compare --file=/path/to/cases.json --connection=default` - Run benchmark cases from a custom file
- `bin/cake instructor_connection_probe --connection=anthropic:default` - Probe a connection and classify config/provider/schema failures with actionable hints
- `bin/cake prompt_style_extract_compare --connection=default` - Run style extraction benchmark cases using default `config/prompt-style-extract-cases.json`
- `bin/cake prompt_style_extract_compare --file=/path/to/cases.json --connection=default` - Run style extraction benchmark cases from a custom file

`prompt_canonicalize` is LLM-only and fails fast if LLM extraction is unavailable or misconfigured.

The JSON output includes `canonical.source`; successful command runs should report `llm`.

Implementation note: [`src/Prompt/Canonicalizer/Canonicalizer.php`](/Users/amoreno/Projects/BeatPrompt/beat-prompt-api/src/Prompt/Canonicalizer/Canonicalizer.php) is the single canonicalization entrypoint used by application layers.

Default benchmark cases live in `config/prompt-canonicalize-cases.json`.

Case file shape for `prompt_canonicalize_compare`:

```json
[
  {
    "input": "Joyner Lucas type beat",
    "expected": {
      "kind": "artist_style_prompt",
      "artists": ["joyner lucas"],
      "target": "beat",
      "modifiers": [],
      "source": "llm"
    }
  }
]
```

For expected failure checks, use:

```json
{
  "input": "Joyner Lucas type beat",
  "expected": {
    "errorContains": "Connection \"missing\" is not defined"
  }
}
```

Style extraction benchmark case shape for `prompt_style_extract_compare`:

```json
[
  {
    "canonical": {
      "kind": "artist_style_prompt",
      "artists": ["joyner lucas"],
      "target": "beat",
      "modifiers": []
    },
    "expected": {
      "genre": "lyrical hip-hop",
      "mood": ["dark", "intense"],
      "energy": "high",
      "tempoBpm": 94,
      "instruments": ["piano", "drums", "bass"],
      "rhythmTraits": ["boom bap", "hard-hitting"],
      "productionTraits": ["punchy", "cinematic"],
      "source": "llm"
    }
  }
]
```
