import React from 'react';
import { cn } from '@/lib/utils';

interface StarRatingProps {
    rating: number;
    maxStars?: number;
    size?: number;
    color?: string;
    className?: string;
    showLabel?: boolean;
}

const StarRating: React.FC<StarRatingProps> = ({
    rating,
    maxStars = 5,
    size = 20,
    color, // Dynamic color passed from parent/settings
    className,
    showLabel = false
}) => {
    // Default fallback color if none provided
    const fillColor = color || 'var(--wpc-star-color, #fbbf24)';
    const emptyColor = '#e2e8f0'; // Gray-200 for empty part

    return (
        <div className={cn("flex items-center gap-2", className)}>
            <div className="flex relative">
                {[...Array(maxStars)].map((_, index) => {
                    const starIndex = index + 1;

                    // Calculate fill percentage for this specific star
                    let fillPercentage = 0;
                    if (rating >= starIndex) {
                        fillPercentage = 100;
                    } else if (rating > index) {
                        fillPercentage = (rating - index) * 100;
                    }

                    return (
                        <div key={index} className="relative" style={{ width: size, height: size }}>
                            {/* Empty Star Background */}
                            <svg
                                width={size}
                                height={size}
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="none"
                                className="absolute top-0 left-0"
                            >
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill={emptyColor} />
                            </svg>

                            {/* Filled Star Overlay (Clipped) */}
                            <div
                                style={{
                                    width: `${fillPercentage}%`,
                                    overflow: 'hidden',
                                    position: 'absolute',
                                    top: 0,
                                    left: 0,
                                    height: '100%'
                                }}
                            >
                                <svg
                                    width={size}
                                    height={size}
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="none"
                                >
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill={fillColor} />
                                </svg>
                            </div>
                        </div>
                    );
                })}
            </div>
            {showLabel && (
                <span className="font-bold text-foreground text-sm">{rating}/{maxStars}</span>
            )}
        </div>
    );
};

export default StarRating;
