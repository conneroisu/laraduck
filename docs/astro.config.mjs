import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  integrations: [
    starlight({
      title: 'Laravel DuckDB',
      description: 'DuckDB integration for Laravel Eloquent ORM - bringing blazing-fast analytical queries to your Laravel applications',
      logo: {
        light: './src/assets/light-logo.svg',
        dark: './src/assets/dark-logo.svg',
        replacesTitle: false,
      },
      social: {
        github: 'https://github.com/laraduck/laraduck',
        discord: 'https://discord.gg/laraduck',
        twitter: 'https://twitter.com/laraduck',
      },
      editLink: {
        baseUrl: 'https://github.com/laraduck/laraduck/edit/main/docs/',
      },
      sidebar: [
        {
          label: 'Getting Started',
          badge: { text: 'Start Here', variant: 'tip' },
          autogenerate: { directory: 'getting-started' },
        },
        {
          label: 'Core Concepts',
          collapsed: false,
          items: [
            { 
              label: 'Analytical Models', 
              link: '/concepts/analytical-models',
              badge: { text: 'Essential', variant: 'caution' }
            },
            { label: 'Query Builder', link: '/concepts/query-builder' },
            { 
              label: 'File Querying', 
              link: '/concepts/file-querying',
              badge: { text: 'Popular', variant: 'success' }
            },
            { label: 'Batch Operations', link: '/concepts/batch-operations' },
          ],
        },
        {
          label: 'Guides',
          collapsed: false,
          items: [
            { 
              label: 'Working with Parquet', 
              link: '/guides/parquet',
              badge: { text: 'Hot', variant: 'danger' }
            },
            { label: 'Window Functions', link: '/guides/window-functions' },
            { label: 'Time Series Analysis', link: '/guides/time-series' },
            { label: 'Data Import/Export', link: '/guides/import-export' },
            { 
              label: 'Performance Optimization', 
              link: '/guides/performance',
              badge: { text: 'Pro', variant: 'note' }
            },
          ],
        },
        {
          label: 'API Reference',
          badge: { text: 'v2.0', variant: 'success' },
          collapsed: true,
          autogenerate: { directory: 'reference' },
        },
        {
          label: 'Examples',
          collapsed: true,
          items: [
            { 
              label: 'E-commerce Analytics', 
              link: '/examples/ecommerce',
              badge: { text: 'New', variant: 'tip' }
            },
            { label: 'Log Analysis', link: '/examples/log-analysis' },
            { label: 'Financial Reports', link: '/examples/financial' },
            { label: 'Real-time Dashboards', link: '/examples/dashboards' },
          ],
        },
        {
          label: 'Resources',
          items: [
            { 
              label: 'Changelog', 
              link: 'https://github.com/laraduck/laraduck/releases',
              attrs: { external: true }
            },
            { 
              label: 'Contributing', 
              link: 'https://github.com/laraduck/laraduck/blob/main/CONTRIBUTING.md',
              attrs: { external: true }
            },
            { 
              label: 'Discord Community', 
              link: 'https://discord.gg/laraduck',
              attrs: { external: true },
              badge: { text: 'Join', variant: 'success' }
            },
          ],
        },
      ],
      customCss: ['./src/styles/custom.css'],
      lastUpdated: true,
      pagination: true,
      head: [
        {
          tag: 'meta',
          attrs: {
            property: 'og:image',
            content: 'https://laraduck.dev/og-image.png',
          },
        },
        {
          tag: 'meta',
          attrs: {
            name: 'twitter:card',
            content: 'summary_large_image',
          },
        },
      ],
      expressiveCode: {
        themes: ['dracula-soft', 'github-light'],
        styleOverrides: {
          borderRadius: '0.75rem',
          borderWidth: '1px',
        },
      },
    }),
  ],
  site: 'https://laraduck.dev',
  base: '/',
});