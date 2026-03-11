tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {                   
                brand: {
                    primary: 'var(--brand-primary)',
                    secondary: 'var(--brand-secondary)',
                    tertiary: 'var(--brand-primary)',
                    dark: 'var(--brand-dark)',
                    card: 'var(--brand-card)',
                    border: 'var(--brand-border)',
                    accent: 'var(--brand-accent)', 
                    accentdark: 'var(--brand-accentdark)',
                    success: 'var(--brand-success)', 
                }
            },
            fontFamily: {
                display: ['ShiftTitle', 'sans-serif'],
                body: ['ShiftBody', 'sans-serif'],
                sans: ['ShiftBody', 'sans-serif'],
            }
        }
    }
}