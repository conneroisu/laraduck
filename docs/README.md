# Laravel DuckDB Documentation

This directory contains the documentation for Laravel DuckDB, built with [Astro Starlight](https://starlight.astro.build/).

## Development

```bash
# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

## Structure

```
docs/
├── src/
│   ├── content/
│   │   └── docs/         # Documentation pages
│   │       ├── getting-started/
│   │       ├── concepts/
│   │       ├── guides/
│   │       ├── reference/
│   │       └── examples/
│   ├── assets/           # Images and static assets
│   └── styles/          # Custom CSS
├── astro.config.mjs     # Astro configuration
└── package.json
```

## Writing Documentation

1. Create new pages in `src/content/docs/`
2. Use frontmatter for metadata:
   ```yaml
   ---
   title: Page Title
   description: Page description
   ---
   ```
3. Write content in Markdown/MDX
4. Use Starlight components for enhanced features

## Deployment

The documentation is automatically deployed to [laraduck.dev](https://laraduck.dev) when changes are pushed to the main branch.

For manual deployment:

```bash
npm run build
# Deploy the dist/ directory
```
