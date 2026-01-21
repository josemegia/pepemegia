export default {
    content: [ './resources/**/*.blade.php', './resources/**/*.js', ],
    theme: {
        extend: {
            fontFamily: {
                'Poppins': ['Poppins', 'sans-serif'],
                'Exo_2': ['Exo 2', 'sans-serif'],
                'Montserrat': ['Montserrat', 'sans-serif'],
                'Lato': ['Lato', 'sans-serif'],
                'Playfair_Display': ['Playfair Display', 'serif'],
                'Nunito': ['Nunito', 'sans-serif'],
                'Comic_Neue': ['Comic Neue', 'cursive'], 
            },
        },
    },
    plugins: [],
    safelist: [
  	'menu-default',
  	'menu-fancy',
        {
            // Patrón para los colores base (bg, text, border)
            pattern: /(bg|text|border)-(blue|purple|pink|gray|black|cyan|fuchsia|orange|amber|yellow|green|white)-(100|200|300|400|500|600|700|800|900)/,
            // Le decimos que genere también las variantes hover para este patrón
            variants: ['hover'],
        },
        {
            // Patrón para los gradientes
            pattern: /(from|via|to)-(blue|purple|pink|gray|black|cyan|fuchsia|orange|amber|yellow|green)-(100|200|300|400|500|600|700|800|900)/,
        },
        // Clases de fuentes que no siguen un patrón
        'font-[Poppins]',
        'font-[Exo_2]',
        'font-[Montserrat]',
        'font-[Lato]',
        'font-[Playfair_Display]',
        'font-[Nunito]',
        'font-[Comic_Neue]',
    ]
}
