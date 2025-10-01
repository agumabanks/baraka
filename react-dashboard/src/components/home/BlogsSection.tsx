import type { BlogItem } from '../../types/home';
import './home.css';

interface BlogsSectionProps {
  items: BlogItem[];
}

const formatDate = (value: string) => {
  const date = new Date(value);
  return date.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  });
};

const BlogsSection = ({ items }: BlogsSectionProps) => {
  if (!items.length) {
    return null;
  }

  return (
    <section className="home-section">
      <div className="home-container">
        <header className="section-header">
          <h2 className="section-title">Blogs</h2>
          <p className="section-lead">Insights for logistics teams shaping the future of delivery.</p>
        </header>
        <div className="grid blogs-grid">
          {items.map((item) => (
            <article key={item.id} className="blog-card">
              <a href={item.link} className="blog-visual" aria-label={item.title}>
                <img src={item.imageUrl} alt="" className="blog-image" />
              </a>
              <div className="blog-body">
                <a href={item.link} className="card-title-link">
                  <h3 className="card-title">{item.title}</h3>
                </a>
                <div className="blog-meta">
                  <span>{item.author}</span>
                  <span>{item.views.toLocaleString()} views</span>
                  <time dateTime={item.updatedAt}>{formatDate(item.updatedAt)}</time>
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default BlogsSection;
