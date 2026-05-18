import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function openCustomLink(
  url: string,
  isNewTab: boolean | undefined,
  isNofollow: boolean | undefined,
  defaultTarget: string = '_blank'
) {
  if (!url) return;
  const finalTarget = isNewTab !== undefined ? (isNewTab ? '_blank' : '_self') : defaultTarget;
  const a = document.createElement('a');
  a.href = url;
  a.target = finalTarget;
  
  const rels = [];
  if (isNofollow) {
    rels.push('nofollow');
  }
  if (finalTarget === '_blank') {
    rels.push('noopener', 'noreferrer');
  }
  if (rels.length > 0) {
    a.rel = rels.join(' ');
  }
  
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
