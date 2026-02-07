import React from 'react';
import Bracket from './components/Bracket';

function App() {
  return (
    <div className="min-h-screen bg-[#f8fafc] py-12">
      <header className="text-center mb-12">
        <h1 className="text-2xl font-bold text-slate-800">Tournament Standings</h1>
        <p className="text-slate-500 text-sm">Match Results & Progression</p>
      </header>

      <main className="container mx-auto">
        <Bracket />
      </main>
    </div>
  );
}

export default App;
