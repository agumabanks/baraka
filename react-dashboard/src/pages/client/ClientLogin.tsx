import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../../components/ui/Button';
import api from '../../services/api';

export const ClientLogin: React.FC = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    email: '',
    phone: '',
    password: '',
    code: '',
  });
  const [isOtpSent, setIsOtpSent] = useState(false);
  const [isOtpLogin, setIsOtpLogin] = useState(true);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSendOtp = async () => {
    setError('');
    setLoading(true);
    try {
      const address = formData.email || formData.phone;
      const channel = formData.phone ? 'sms' : 'email';
      
      await api.post('/v10/customer/send-otp', { address, channel });
      setIsOtpSent(true);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to send OTP');
    } finally {
      setLoading(false);
    }
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const payload: any = {};
      
      if (isOtpLogin) {
        payload.code = formData.code;
        if (formData.email) payload.email = formData.email;
        if (formData.phone) payload.phone = formData.phone;
      } else {
        payload.password = formData.password;
        if (formData.email) payload.email = formData.email;
        if (formData.phone) payload.phone = formData.phone;
      }

      const response = await api.post('/v10/customer/login', payload);
      
      if (response.data.success && response.data.token) {
        localStorage.setItem('client_token', response.data.token);
        navigate('/client/dashboard');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-md w-full">
        <div className="bg-white rounded-2xl shadow-xl p-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h1>
            <p className="text-gray-600">Login to track and manage your shipments</p>
          </div>

          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              {error}
            </div>
          )}

          <div className="mb-6 flex gap-2">
            <button
              onClick={() => setIsOtpLogin(true)}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-colors ${
                isOtpLogin
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              OTP Login
            </button>
            <button
              onClick={() => setIsOtpLogin(false)}
              className={`flex-1 py-2 px-4 rounded-lg font-medium transition-colors ${
                !isOtpLogin
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              Password Login
            </button>
          </div>

          <form onSubmit={handleLogin} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email or Phone
              </label>
              <input
                type="text"
                value={formData.email || formData.phone}
                onChange={(e) => {
                  const value = e.target.value;
                  if (value.includes('@')) {
                    setFormData({ ...formData, email: value, phone: '' });
                  } else {
                    setFormData({ ...formData, phone: value, email: '' });
                  }
                }}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your email or phone"
                required
              />
            </div>

            {isOtpLogin ? (
              <>
                {!isOtpSent ? (
                  <Button
                    type="button"
                    onClick={handleSendOtp}
                    disabled={loading}
                    className="w-full"
                  >
                    {loading ? 'Sending...' : 'Send OTP'}
                  </Button>
                ) : (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Enter OTP Code
                      </label>
                      <input
                        type="text"
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                        className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter 6-digit code"
                        required
                      />
                    </div>
                    <Button type="submit" disabled={loading} className="w-full">
                      {loading ? 'Logging in...' : 'Login with OTP'}
                    </Button>
                    <button
                      type="button"
                      onClick={handleSendOtp}
                      className="w-full text-sm text-blue-600 hover:text-blue-700"
                    >
                      Resend OTP
                    </button>
                  </>
                )}
              </>
            ) : (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Password
                  </label>
                  <input
                    type="password"
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your password"
                    required
                  />
                </div>
                <Button type="submit" disabled={loading} className="w-full">
                  {loading ? 'Logging in...' : 'Login'}
                </Button>
              </>
            )}
          </form>

          <div className="mt-6 text-center">
            <p className="text-sm text-gray-600">
              Don't have an account?{' '}
              <button
                onClick={() => navigate('/client/register')}
                className="text-blue-600 hover:text-blue-700 font-medium"
              >
                Register here
              </button>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ClientLogin;
