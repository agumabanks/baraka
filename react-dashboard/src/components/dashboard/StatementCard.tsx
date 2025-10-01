import React from 'react';

interface StatementCardProps {
  title: string;
  income: number;
  expense: number;
  currency?: string;
  loading?: boolean;
}

/**
 * Statement Card Component
 * Displays financial statement matching Blade list-group format
 * Pure monochrome styling
 */
const StatementCard: React.FC<StatementCardProps> = ({
  title,
  income,
  expense,
  currency = '$',
  loading = false,
}) => {
  const balance = income - expense;
  
  if (loading) {
    return (
      <div className="bg-mono-white border border-mono-gray-200 rounded-2xl shadow-lg overflow-hidden">
        <div className="border-b border-mono-gray-200 bg-mono-gray-50 px-6 py-4">
          <div className="skeleton h-5 w-32"></div>
        </div>
        <div className="p-6 space-y-4">
          <div className="flex justify-between">
            <div className="skeleton h-4 w-16"></div>
            <div className="skeleton h-4 w-24"></div>
          </div>
          <div className="flex justify-between">
            <div className="skeleton h-4 w-16"></div>
            <div className="skeleton h-4 w-24"></div>
          </div>
          <div className="border-t border-mono-gray-200 pt-4">
            <div className="flex justify-between">
              <div className="skeleton h-5 w-20"></div>
              <div className="skeleton h-5 w-28"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }
  
  return (
    <div className="bg-mono-white border border-mono-gray-200 rounded-2xl shadow-lg overflow-hidden">
      <div className="border-b border-mono-gray-200 bg-mono-gray-50 px-6 py-4">
        <h3 className="text-base font-semibold text-mono-gray-900">{title}</h3>
      </div>
      <div className="p-6 space-y-4">
        <div className="flex justify-between items-center">
          <span className="text-sm font-medium text-mono-gray-600">Income</span>
          <span className="text-base font-semibold text-mono-black">
            {currency}{income.toLocaleString()}
          </span>
        </div>
        <div className="flex justify-between items-center">
          <span className="text-sm font-medium text-mono-gray-600">Expense</span>
          <span className="text-base font-semibold text-mono-gray-700">
            {currency}{expense.toLocaleString()}
          </span>
        </div>
        <div className="border-t border-mono-gray-200 pt-4">
          <div className="flex justify-between items-center">
            <span className="text-sm font-bold text-mono-gray-900">Balance</span>
            <span className="text-lg font-bold text-mono-black">
              {currency}{balance.toLocaleString()}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StatementCard;