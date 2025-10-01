import BannerSection from '../components/home/BannerSection';
import ServicesSection from '../components/home/ServicesSection';
import WhyCourierSection from '../components/home/WhyCourierSection';
import PricingSection from '../components/home/PricingSection';
import AchievementsSection from '../components/home/AchievementsSection';
import PartnersSection from '../components/home/PartnersSection';
import BlogsSection from '../components/home/BlogsSection';
import { mockHomeData } from '../data/mockHomeData';
import type { HomePageContent } from '../types/home';

interface HomeProps {
  data?: HomePageContent;
}

const Home = ({ data = mockHomeData }: HomeProps) => {
  const pageData = data ?? mockHomeData;

  const handleTrack = (trackingId: string) => {
    console.log('Track parcel:', trackingId);
  };

  return (
    <div className="home-page">
      <BannerSection content={pageData.banner} onTrack={handleTrack} />
      <ServicesSection services={pageData.services} />
      <WhyCourierSection items={pageData.whyCourier} companyName="Baraka Courier" />
      <PricingSection tiers={pageData.pricing} currency="$" />
      <AchievementsSection items={pageData.achievements} />
      <PartnersSection partners={pageData.partners} />
      <BlogsSection items={pageData.blogs} />
    </div>
  );
};

export default Home;
