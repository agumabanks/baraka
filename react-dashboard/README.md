# React Dashboard

A React-based dashboard application built with Vite, TypeScript, and Tailwind CSS, featuring a monochrome design system inspired by Steve Jobs' minimalist philosophy.

## 🚀 Getting Started

### Prerequisites
- Node.js (v16 or higher)
- npm or yarn

### Installation

1. Navigate to the react-dashboard directory:
   ```bash
   cd react-dashboard
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Start the development server:
   ```bash
   npm run dev
   ```

4. Open your browser and visit `http://localhost:5173`

## 📁 Project Structure

```
react-dashboard/
├── src/
│   ├── components/
│   │   ├── ui/           # Reusable UI primitives
│   │   ├── layout/       # Layout components
│   │   └── dashboard/    # Dashboard-specific components
│   ├── hooks/            # Custom React hooks
│   ├── services/         # API layer and external services
│   ├── stores/           # State management (Zustand)
│   ├── types/            # TypeScript type definitions
│   ├── utils/            # Utility functions
│   ├── App.tsx           # Main application component
│   └── index.css         # Global styles with Tailwind
├── public/               # Static assets
├── package.json
├── tailwind.config.js    # Tailwind CSS configuration
├── postcss.config.js     # PostCSS configuration
└── tsconfig.json         # TypeScript configuration
```

## 🎨 Monochrome Design System

### Color Palette
The design system uses a 12-shade grayscale palette:

- `mono-black`: #000000 (primary actions, headings)
- `mono-gray-900`: #1a1a1a (primary text)
- `mono-gray-800`: #2d2d2d
- `mono-gray-700`: #404040 (secondary text, icons)
- `mono-gray-600`: #666666
- `mono-gray-500`: #808080 (muted text)
- `mono-gray-400`: #999999 (disabled states)
- `mono-gray-300`: #b3b3b3 (borders)
- `mono-gray-200`: #cccccc
- `mono-gray-100`: #e6e6e6 (subtle backgrounds)
- `mono-gray-50`: #f5f5f5 (card backgrounds)
- `mono-white`: #ffffff (page background)

### Typography
- **Font Family**: System font stack (-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif)
- **Headings**: 600 weight, -0.02em letter-spacing
- **Body**: 1.6 line-height

### Shadows
- `shadow-subtle`: 0 1px 3px rgba(0, 0, 0, 0.08)
- `shadow-normal`: 0 2px 8px rgba(0, 0, 0, 0.12)
- `shadow-elevated`: 0 4px 16px rgba(0, 0, 0, 0.16)

### Usage in Tailwind
```tsx
<div className="bg-mono-white text-mono-gray-900 border border-mono-gray-200 shadow-subtle">
  Content
</div>
```

## 🛠️ Tech Stack

- **Framework**: React 18 with TypeScript
- **Build Tool**: Vite
- **Styling**: Tailwind CSS with custom monochrome theme
- **Routing**: React Router v6
- **State Management**: Zustand
- **Data Fetching**: TanStack Query (React Query)
- **Icons**: Lucide React
- **Charts**: Recharts

## 🔄 Next Steps

1. **Laravel Integration**
   - Set up API endpoints in Laravel
   - Configure authentication
   - Implement data fetching with React Query

2. **Component Development**
   - Convert Blade components to React
   - Build reusable UI components
   - Implement dashboard widgets

3. **Feature Implementation**
   - Dashboard overview page
   - Analytics and reporting
   - User management
   - Settings and configuration

4. **Testing & Optimization**
   - Add unit and integration tests
   - Performance optimization
   - Accessibility improvements

## 📝 Development Guidelines

- Follow the monochrome design principles: simplicity, focus, and elegance
- Use TypeScript for type safety
- Implement responsive design with Tailwind's mobile-first approach
- Keep components modular and reusable
- Follow React best practices and hooks patterns

## 🤝 Contributing

This project follows the existing Laravel application's coding standards and design philosophy. Ensure all contributions maintain the monochrome aesthetic and functional excellence.

---

*Inspired by Steve Jobs' design philosophy: "Simplicity is the ultimate sophistication."*
