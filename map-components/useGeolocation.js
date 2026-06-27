import { useState, useEffect } from 'react';

/**
 * Returns { coords, error, loading }
 * coords = { latitude, longitude } or null
 */
export default function useGeolocation() {
  const [coords, setCoords]   = useState(null);
  const [error, setError]     = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!navigator.geolocation) {
      setError('La géolocalisation n\'est pas supportée par ce navigateur.');
      setLoading(false);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        setCoords({ latitude: coords.latitude, longitude: coords.longitude });
        setLoading(false);
      },
      (err) => {
        setError('Permission de localisation refusée.');
        setLoading(false);
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  }, []);

  return { coords, error, loading };
}
