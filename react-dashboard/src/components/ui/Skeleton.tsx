import React from 'react';

interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {}

export const Skeleton: React.FC<SkeletonProps> = ({ className = '', ...rest }) => (
  <div
    className={`animate-pulse rounded-md bg-mono-gray-200/80 ${className}`.trim()}
    {...rest}
  />
);

export default Skeleton;
