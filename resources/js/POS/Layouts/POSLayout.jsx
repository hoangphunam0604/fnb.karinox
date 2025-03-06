// File: resources/js/POS/Layouts/POSLayout.jsx

import React from 'react';

const POSLayout = ({ children }) => {
    return (
        <div className="pos-layout">
            <header className="bg-blue-500 text-white p-4">
                <h1>POS Layout Header</h1>
            </header>
            <main className="p-4">
                {children}
            </main>
        </div>
    );
};

export default POSLayout;
