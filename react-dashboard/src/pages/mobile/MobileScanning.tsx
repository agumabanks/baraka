import React, { useState, useEffect, useRef } from 'react';
import { BrowserMultiFormatReader, BarcodeFormat } from '@zxing/library';
import { QrReader } from 'react-qr-reader';
import { useMutation, useQuery } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { io, Socket } from 'socket.io-client';
import { 
  Camera, 
  Scan, 
  Package, 
  CheckCircle, 
  XCircle, 
  AlertCircle, 
  RefreshCw,
  Wifi, 
  WifiOff,
  Settings,
  History,
  Download,
  Upload
} from 'lucide-react';
import Button from '../../components/ui/Button';
import LoadingSpinner from '../../components/ui/LoadingSpinner';

interface ScanResult {
  tracking_number: string;
  action: 'inbound' | 'outbound' | 'delivery' | 'exception' | 'manual_intervention';
  location_id: number;
  timestamp: string;
  notes?: string;
  offline_sync_key?: string;
  latitude?: number;
  longitude?: number;
  barcode_type?: 'qr' | 'barcode' | 'sscc' | 'sscc18';
  accuracy?: number;
}

interface DeviceInfo {
  device_id: string;
  device_name: string;
  platform: string;
  app_version: string;
  is_active: boolean;
}

interface ScanEvent {
  id: string;
  tracking_number: string;
  action: string;
  status: string;
  timestamp: string;
  branch: string;
  synced: boolean;
}

const MobileScanning: React.FC = () => {
  // State management
  const [cameraActive, setCameraActive] = useState(false);
  const [scanMode, setScanMode] = useState<'single' | 'bulk' | 'batch'>('single');
  const [isOnline, setIsOnline] = useState(navigator.onLine);
  const [deviceInfo, setDeviceInfo] = useState<DeviceInfo | null>(null);
  const [pendingScans, setPendingScans] = useState<ScanResult[]>([]);
  const [scanHistory, setScanHistory] = useState<ScanEvent[]>([]);
  const [locationId, setLocationId] = useState<number>(1); // Default branch ID
  const [lastScannedCode, setLastScannedCode] = useState<string>('');
  const [barcodeType, setBarcodeType] = useState<'qr' | 'barcode' | 'sscc' | 'sscc18'>('barcode');

  // Refs
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const codeReader = useRef(new BrowserMultiFormatReader());
  const socket = useRef<Socket | null>(null);

  // Mock branches data (replace with API call)
  const branches = [
    { id: 1, name: 'Main Hub', code: 'MH001' },
    { id: 2, name: 'North Branch', code: 'NB002' },
    { id: 3, name: 'South Branch', code: 'SB003' },
  ];

  // Device authentication
  const authenticateDevice = async () => {
    const deviceId = await getDeviceId();
    const response = await fetch('/api/v1/devices/authenticate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        device_id: deviceId,
        device_token: localStorage.getItem('device_token') || generateDeviceToken()
      })
    });
    return response.json();
  };

  // Mobile scan API mutation
  const mobileScanMutation = useMutation({
    mutationFn: async (scanData: ScanResult) => {
      const response = await fetch('/api/v1/mobile/scan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Device-ID': await getDeviceId(),
          'X-Device-Token': localStorage.getItem('device_token') || '',
          'X-App-Version': '1.0.0',
        },
        body: JSON.stringify(scanData),
      });
      
      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error || 'Scan failed');
      }
      
      return response.json();
    },
    onSuccess: (data, scanData) => {
      toast.success(`Scan successful: ${scanData.tracking_number}`);
      setLastScannedCode(scanData.tracking_number);
      
      // Add to history
      const event: ScanEvent = {
        id: Date.now().toString(),
        tracking_number: scanData.tracking_number,
        action: scanData.action,
        status: 'success',
        timestamp: new Date().toISOString(),
        branch: branches.find(b => b.id === scanData.location_id)?.name || 'Unknown',
        synced: isOnline,
      };
      setScanHistory(prev => [event, ...prev.slice(0, 49)]); // Keep last 50 scans
      
      // Store locally for offline sync
      storeScanLocally(data, scanData);
      
      // Auto-clear last scanned after 5 seconds
      setTimeout(() => setLastScannedCode(''), 5000);
    },
    onError: (error: Error) => {
      toast.error(`Scan failed: ${error.message}`);
      
      // Store failed scan for later sync
      const failedScan: ScanResult = {
        ...JSON.parse(error.message),
        offline_sync_key: generateSyncKey()
      };
      setPendingScans(prev => [...prev, failedScan]);
      storeScanLocally({ success: false }, failedScan);
    },
  });

  // Bulk scan mutation
  const bulkScanMutation = useMutation({
    mutationFn: async (scans: ScanResult[]) => {
      const response = await fetch('/api/v1/mobile/bulk-scan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Device-ID': await getDeviceId(),
          'X-Device-Token': localStorage.getItem('device_token') || '',
        },
        body: JSON.stringify({
          scans: scans.map(scan => ({ ...scan, batch_id: generateBatchId() })),
          batch_id: generateBatchId(),
        }),
      });
      
      if (!response.ok) {
        throw new Error('Bulk scan failed');
      }
      
      return response.json();
    },
    onSuccess: (data) => {
      toast.success(`Bulk scan completed: ${data.processed} successful, ${data.failed} failed`);
      if (data.conflicts > 0) {
        toast(`${data.conflicts} conflicts detected`, { icon: '⚠️' });
      }
    },
    onError: () => {
      toast.error('Bulk scan failed');
    },
  });

  // Offline sync mutation
  const syncOfflineMutation = useMutation({
    mutationFn: async () => {
      const pendingScans = await getPendingScans();
      if (pendingScans.length === 0) return { success: true };
      
      const response = await fetch('/api/v1/mobile/enhanced-offline-sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Device-ID': await getDeviceId(),
          'X-Device-Token': localStorage.getItem('device_token') || '',
        },
        body: JSON.stringify({ pending_scans: pendingScans }),
      });
      
      if (!response.ok) {
        throw new Error('Sync failed');
      }
      
      return response.json();
    },
    onSuccess: (data) => {
      toast.success(`Synced ${data.sync_count} scans`);
      setPendingScans([]);
      clearPendingScans();
    },
  });

  // Initialize camera and device
  useEffect(() => {
    initializeDevice();
    setupNetworkListener();
    setupWebSocket();
    loadPendingScans();
    
    return () => {
      cleanup();
    };
  }, []);

  // Camera setup
  const initializeCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { 
          facingMode: 'environment', // Use back camera
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      });
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        setCameraActive(true);
      }
    } catch (error) {
      console.error('Camera access failed:', error);
      toast.error('Camera access denied or not available');
    }
  };

  // Barcode scanning
  const startScanning = async () => {
    if (!cameraActive) {
      await initializeCamera();
    }
    
    try {
      const result = await codeReader.current.decodeFromVideoDevice(
        undefined, // Use default camera
        videoRef.current!,
        (result, error) => {
          if (result) {
            handleScanResult(result.getText());
          }
        }
      );
    } catch (error) {
      console.error('Scanning failed:', error);
      toast.error('Scanning failed');
    }
  };

  // Handle scan result
  const handleScanResult = (code: string) => {
    if (code === lastScannedCode) return; // Prevent duplicate scans
    
    setLastScannedCode(code);
    
    // Auto-detect barcode type
    const detectedType = detectBarcodeType(code);
    setBarcodeType(detectedType);
    
    // Create scan result
    const scanResult: ScanResult = {
      tracking_number: code,
      action: getDefaultAction(),
      location_id: locationId,
      timestamp: new Date().toISOString(),
      barcode_type: detectedType,
      offline_sync_key: generateSyncKey(),
    };
    
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          scanResult.latitude = position.coords.latitude;
          scanResult.longitude = position.coords.longitude;
          scanResult.accuracy = position.coords.accuracy;
          mobileScanMutation.mutate(scanResult);
        },
        () => {
          mobileScanMutation.mutate(scanResult);
        }
      );
    } else {
      mobileScanMutation.mutate(scanResult);
    }
  };

  // Manual scan (keyboard input)
  const handleManualScan = (trackingNumber: string) => {
    if (trackingNumber.trim()) {
      handleScanResult(trackingNumber.trim());
    }
  };

  // Sync offline scans
  const syncOfflineScans = () => {
    if (isOnline && pendingScans.length > 0) {
      syncOfflineMutation.mutate();
    }
  };

  // Utility functions
  const getDeviceId = async (): Promise<string> => {
    let deviceId = localStorage.getItem('device_id');
    if (!deviceId) {
      deviceId = `mobile_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
      localStorage.setItem('device_id', deviceId);
    }
    return deviceId;
  };

  const generateDeviceToken = (): string => {
    const token = Math.random().toString(36).substr(2, 32);
    localStorage.setItem('device_token', token);
    return token;
  };

  const generateSyncKey = (): string => {
    return `sync_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
  };

  const generateBatchId = (): string => {
    return `batch_${Date.now()}`;
  };

  const detectBarcodeType = (code: string): 'qr' | 'barcode' | 'sscc' | 'sscc18' => {
    if (code.startsWith('SSCC')) return 'sscc';
    if (code.length === 18 && /^\d{18}$/.test(code)) return 'sscc18';
    if (code.startsWith('http') || code.includes('QR')) return 'qr';
    return 'barcode';
  };

  const getDefaultAction = (): ScanResult['action'] => {
    // Logic to determine default action based on context
    return 'inbound';
  };

  // Local storage functions
  const storeScanLocally = (data: any, scanData: ScanResult) => {
    localStorage.setItem(`scan_${scanData.offline_sync_key}`, JSON.stringify({
      data,
      scanData,
      timestamp: new Date().toISOString()
    }));
  };

  const getPendingScans = async (): Promise<any[]> => {
    const scans = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key?.startsWith('scan_')) {
        const scan = JSON.parse(localStorage.getItem(key) || '{}');
        if (!scan.data?.success) {
          scans.push(scan.scanData);
        }
      }
    }
    return scans;
  };

  const clearPendingScans = () => {
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('scan_')) {
        localStorage.removeItem(key);
      }
    });
  };

  // Setup functions
  const initializeDevice = async () => {
    try {
      const authResult = await authenticateDevice();
      if (authResult.success) {
        setDeviceInfo(authResult.device);
      }
    } catch (error) {
      console.error('Device authentication failed:', error);
    }
  };

  const setupNetworkListener = () => {
    const handleOnline = () => {
      setIsOnline(true);
      toast.success('Back online');
      syncOfflineScans();
    };
    
    const handleOffline = () => {
      setIsOnline(false);
      toast.error('You are offline - scans will be stored locally');
    };
    
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    
    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  };

  const setupWebSocket = () => {
    const socketUrl = (import.meta.env.VITE_WEBSOCKET_URL as string | undefined) || 'ws://localhost:3001';
    socket.current = io(socketUrl);
    socket.current.on('connect', () => {
      console.log('Connected to WebSocket');
    });
    socket.current.on('scan_event', (data) => {
      // Handle real-time scan events
      console.log('Received scan event:', data);
    });
  };

  const loadPendingScans = async () => {
    const scans = await getPendingScans();
    setPendingScans(scans);
  };

  const cleanup = () => {
    if (socket.current) {
      socket.current.disconnect();
    }
    if (videoRef.current?.srcObject) {
      const stream = videoRef.current.srcObject as MediaStream;
      stream.getTracks().forEach(track => track.stop());
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 p-4 pb-20">
      {/* Header */}
      <div className="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div className="flex items-center justify-between mb-4">
          <h1 className="text-xl font-bold text-gray-900">Mobile Scanning</h1>
          <div className="flex items-center gap-2">
            {isOnline ? (
              <Wifi className="w-5 h-5 text-green-500" />
            ) : (
              <WifiOff className="w-5 h-5 text-red-500" />
            )}
            <span className={`text-sm ${isOnline ? 'text-green-600' : 'text-red-600'}`}>
              {isOnline ? 'Online' : 'Offline'}
            </span>
          </div>
        </div>

        {/* Device Info */}
        {deviceInfo && (
          <div className="bg-gray-50 rounded-lg p-3 mb-4">
            <p className="text-sm text-gray-600">Device: {deviceInfo.device_name}</p>
            <p className="text-sm text-gray-600">Platform: {deviceInfo.platform}</p>
          </div>
        )}

        {/* Scan Mode Selector */}
        <div className="flex gap-2 mb-4">
          {['single', 'bulk', 'batch'].map((mode) => (
            <button
              key={mode}
              onClick={() => setScanMode(mode as any)}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                scanMode === mode
                  ? 'bg-blue-500 text-white'
                  : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
              }`}
            >
              {mode.charAt(0).toUpperCase() + mode.slice(1)}
            </button>
          ))}
        </div>

        {/* Branch Selector */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Current Location
          </label>
          <select
            value={locationId}
            onChange={(e) => setLocationId(Number(e.target.value))}
            className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            {branches.map((branch) => (
              <option key={branch.id} value={branch.id}>
                {branch.name} ({branch.code})
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Camera View */}
      <div className="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div className="relative">
          <video
            ref={videoRef}
            className="w-full h-64 bg-gray-900 rounded-lg"
            autoPlay
            playsInline
            muted
          />
          
          {/* Scanner Overlay */}
          {cameraActive && (
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="w-48 h-48 border-2 border-blue-500 rounded-lg relative">
                <div className="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-blue-500"></div>
                <div className="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-blue-500"></div>
                <div className="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-blue-500"></div>
                <div className="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-blue-500"></div>
              </div>
            </div>
          )}
        </div>

        {/* Camera Controls */}
        <div className="flex gap-3 mt-4">
          <Button
            onClick={startScanning}
            disabled={mobileScanMutation.isPending}
            className="flex-1 flex items-center justify-center gap-2"
          >
            <Scan className="w-5 h-5" />
            {mobileScanMutation.isPending ? 'Scanning...' : 'Start Scan'}
          </Button>
          
          <Button
            onClick={() => setCameraActive(false)}
            variant="secondary"
            className="px-4"
          >
            <Camera className="w-5 h-5" />
          </Button>
        </div>
      </div>

      {/* Manual Input */}
      <div className="bg-white rounded-lg shadow-sm p-4 mb-4">
        <h3 className="text-lg font-semibold text-gray-900 mb-3">Manual Input</h3>
        <div className="flex gap-2">
          <input
            type="text"
            placeholder="Enter tracking number"
            className="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            onKeyPress={(e) => {
              if (e.key === 'Enter') {
                handleManualScan((e.target as HTMLInputElement).value);
                (e.target as HTMLInputElement).value = '';
              }
            }}
          />
          <Button onClick={() => {}}>
            <CheckCircle className="w-5 h-5" />
          </Button>
        </div>
      </div>

      {/* Last Scanned */}
      {lastScannedCode && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
          <div className="flex items-center gap-2 text-green-800">
            <CheckCircle className="w-5 h-5" />
            <span className="font-medium">Last Scanned:</span>
            <span className="font-mono">{lastScannedCode}</span>
          </div>
        </div>
      )}

      {/* Pending Sync */}
      {pendingScans.length > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-yellow-800">
              <RefreshCw className="w-5 h-5" />
              <span>{pendingScans.length} pending scans</span>
            </div>
            {isOnline && (
              <Button
                onClick={syncOfflineScans}
                size="sm"
                className="bg-yellow-600 hover:bg-yellow-700"
              >
                Sync Now
              </Button>
            )}
          </div>
        </div>
      )}

      {/* Recent Scans */}
      <div className="bg-white rounded-lg shadow-sm p-4">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-lg font-semibold text-gray-900">Recent Scans</h3>
          <History className="w-5 h-5 text-gray-500" />
        </div>
        
        <div className="space-y-2 max-h-64 overflow-y-auto">
          {scanHistory.map((scan) => (
            <div key={scan.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div>
                <p className="font-medium text-gray-900">{scan.tracking_number}</p>
                <p className="text-sm text-gray-600">{scan.action} • {scan.branch}</p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-500">
                  {new Date(scan.timestamp).toLocaleTimeString()}
                </p>
                <p className={`text-xs ${scan.synced ? 'text-green-600' : 'text-orange-600'}`}>
                  {scan.synced ? 'Synced' : 'Pending'}
                </p>
              </div>
            </div>
          ))}
          
          {scanHistory.length === 0 && (
            <p className="text-center text-gray-500 py-4">No recent scans</p>
          )}
        </div>
      </div>

      {/* Loading Overlay */}
      {(mobileScanMutation.isPending || bulkScanMutation.isPending || syncOfflineMutation.isPending) && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 flex flex-col items-center">
            <LoadingSpinner size="lg" />
            <p className="mt-3 text-gray-600">
              {mobileScanMutation.isPending && 'Processing scan...'}
              {bulkScanMutation.isPending && 'Processing bulk scan...'}
              {syncOfflineMutation.isPending && 'Syncing offline scans...'}
            </p>
          </div>
        </div>
      )}
    </div>
  );
};

export default MobileScanning;